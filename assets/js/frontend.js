(function($) {
    'use strict';

    const PBS = {
        data: pbsData || {},
        calendar: null,
        selectedDate: null,
        selectedTime: null,
        selectedService: null,
        
        /**
         * Inicializar el plugin
         */
        init: function() {
            this.cacheDOMElements();
            this.bindEvents();
            this.loadServices();
        },

        /**
         * Cachear elementos del DOM
         */
        cacheDOMElements: function() {
            this.$container = $('.pbs-booking-form');
            this.$serviceSelect = this.$container.find('.pbs-service-select');
            this.$dateInput = this.$container.find('.pbs-date-input');
            this.$calendarContainer = this.$container.find('.pbs-calendar');
            this.$timesContainer = this.$container.find('.pbs-times-container');
            this.$submitBtn = this.$container.find('.pbs-submit-btn');
            this.$form = this.$container.find('form');
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            const self = this;

            // Cambio de servicio
            this.$serviceSelect.on('change', function() {
                self.selectedService = $(this).val();
                self.selectedDate = null;
                self.selectedTime = null;
                self.updateCalendar();
            });

            // Selección de fecha
            this.$calendarContainer.on('click', '.pbs-day', function() {
                if (!$(this).hasClass('disabled')) {
                    self.selectDate($(this).data('date'));
                }
            });

            // Selección de hora
            this.$timesContainer.on('click', '.pbs-time-slot', function() {
                if (!$(this).hasClass('disabled')) {
                    self.selectTime($(this).data('time'));
                }
            });

            // Envío del formulario
            this.$form.on('submit', function(e) {
                e.preventDefault();
                self.submitBooking();
            });

            // Iniciar con primer servicio si hay
            if (this.$serviceSelect.find('option').length > 1) {
                this.$serviceSelect.trigger('change');
            }
        },

        /**
         * Cargar lista de servicios
         */
        loadServices: function() {
            const self = this;

            $.ajax({
                url: this.data.restUrl + 'services',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && Array.isArray(response)) {
                        self.renderServices(response);
                    }
                },
                error: function(error) {
                    console.error('Error loading services:', error);
                    self.showError('No se pudieron cargar los servicios');
                }
            });
        },

        /**
         * Renderizar servicios en select
         */
        renderServices: function(services) {
            this.$serviceSelect.empty();
            this.$serviceSelect.append('<option value="">Selecciona un servicio</option>');

            services.forEach(function(service) {
                const option = $('<option></option>')
                    .val(service.id)
                    .text(service.name + ' - $' + parseFloat(service.price).toFixed(2));
                this.$serviceSelect.append(option);
            }.bind(this));
        },

        /**
         * Actualizar calendario con disponibilidad
         */
        updateCalendar: function() {
            if (!this.selectedService) {
                this.$calendarContainer.html('<p>Selecciona un servicio primero</p>');
                return;
            }

            this.renderCalendar();
        },

        /**
         * Renderizar calendario
         */
        renderCalendar: function() {
            const today = new Date();
            const startDate = new Date(today);
            const endDate = new Date(today);
            endDate.setDate(endDate.getDate() + 60); // 60 días adelante

            let html = '<div class="pbs-calendar-header">';
            html += '<button class="pbs-prev-month" type="button">&lt;</button>';
            html += '<span class="pbs-month-year">' + this.getMonthYear(today) + '</span>';
            html += '<button class="pbs-next-month" type="button">&gt;</button>';
            html += '</div>';
            html += '<div class="pbs-calendar-days">';
            html += '<div class="pbs-weekdays">';
            html += '<div>Do</div><div>Lu</div><div>Ma</div><div>Mi</div><div>Ju</div><div>Vi</div><div>Sa</div>';
            html += '</div>';
            html += '<div class="pbs-days-container">';

            let currentDate = new Date(startDate);
            currentDate.setDate(currentDate.getDate() - currentDate.getDay());

            while (currentDate < endDate) {
                const dateStr = this.formatDate(currentDate, 'YYYY-MM-DD');
                const isToday = currentDate.toDateString() === today.toDateString();
                const isPast = currentDate < today;
                const isDisabled = isPast;

                let classes = 'pbs-day';
                if (isToday) classes += ' today';
                if (isDisabled) classes += ' disabled';
                if (this.selectedDate === dateStr) classes += ' selected';

                html += '<div class="' + classes + '" data-date="' + dateStr + '">';
                html += currentDate.getDate();
                html += '</div>';

                currentDate.setDate(currentDate.getDate() + 1);
            }

            html += '</div></div>';
            this.$calendarContainer.html(html);

            // Vincular navegación del calendario
            const self = this;
            this.$calendarContainer.find('.pbs-prev-month').on('click', function(e) {
                e.preventDefault();
                // Implementar navegación anterior
            });

            this.$calendarContainer.find('.pbs-next-month').on('click', function(e) {
                e.preventDefault();
                // Implementar navegación siguiente
            });
        },

        /**
         * Seleccionar fecha
         */
        selectDate: function(date) {
            this.selectedDate = date;
            this.selectedTime = null;
            this.$calendarContainer.find('.pbs-day').removeClass('selected');
            this.$calendarContainer.find('[data-date="' + date + '"]').addClass('selected');
            this.loadAvailableTimes(date);
        },

        /**
         * Cargar horas disponibles
         */
        loadAvailableTimes: function(date) {
            const self = this;

            $.ajax({
                url: this.data.restUrl + 'availability/day',
                method: 'GET',
                data: {
                    service_id: this.selectedService,
                    date: date
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.slots) {
                        self.renderTimes(response.slots);
                    }
                },
                error: function(error) {
                    console.error('Error loading times:', error);
                    self.showError('No se pudieron cargar las horas disponibles');
                }
            });
        },

        /**
         * Renderizar horas disponibles
         */
        renderTimes: function(slots) {
            let html = '<div class="pbs-times-list">';

            if (slots.length === 0) {
                html += '<p class="pbs-no-slots">No hay horas disponibles para esta fecha</p>';
            } else {
                slots.forEach(function(slot) {
                    const isAvailable = slot.available;
                    const classes = 'pbs-time-slot ' + (isAvailable ? '' : 'disabled');
                    html += '<button type="button" class="' + classes + '" data-time="' + slot.time + '">';
                    html += slot.time;
                    html += '</button>';
                });
            }

            html += '</div>';
            this.$timesContainer.html(html);
        },

        /**
         * Seleccionar hora
         */
        selectTime: function(time) {
            this.selectedTime = time;
            this.$timesContainer.find('.pbs-time-slot').removeClass('selected');
            this.$timesContainer.find('[data-time="' + time + '"]').addClass('selected');
            this.$submitBtn.prop('disabled', false);
        },

        /**
         * Enviar reserva
         */
        submitBooking: function() {
            if (!this.selectedService || !this.selectedDate || !this.selectedTime) {
                this.showError('Por favor completa todos los campos');
                return;
            }

            // Obtener datos del formulario
            const formData = {
                service_id: this.selectedService,
                name: this.$form.find('[name="customer_name"]').val(),
                email: this.$form.find('[name="customer_email"]').val(),
                phone: this.$form.find('[name="customer_phone"]').val(),
                date: this.selectedDate,
                time: this.selectedTime,
                notes: this.$form.find('[name="customer_notes"]').val(),
                csrf_token: window.pbsSecurity ? window.pbsSecurity.csrf_token : ''
            };

            // Validar email
            if (!this.isValidEmail(formData.email)) {
                this.showError('Por favor ingresa un email válido');
                return;
            }

            // Validar nombre
            if (!formData.name || formData.name.trim() === '') {
                this.showError('Por favor ingresa tu nombre');
                return;
            }

            this.createBooking(formData);
        },

        /**
         * Crear reserva
         */
        createBooking: function(data) {
            const self = this;
            this.$submitBtn.prop('disabled', true);
            this.showLoading('Creando reserva...');

            $.ajax({
                url: this.data.restUrl + 'bookings/create',
                method: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                headers: {
                    'X-CSRF-Token': window.pbsSecurity ? window.pbsSecurity.csrf_token : ''
                },
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        self.handleBookingSuccess(response);
                    } else {
                        self.showError(response.error || 'Error al crear la reserva');
                        self.$submitBtn.prop('disabled', false);
                    }
                },
                error: function(error) {
                    console.error('Error creating booking:', error);
                    self.showError('Error al crear la reserva');
                    self.$submitBtn.prop('disabled', false);
                }
            });
        },

        /**
         * Manejar éxito de creación de reserva
         */
        handleBookingSuccess: function(response) {
            if (response.requires_payment && response.payment_url) {
                // Redirigir a pago
                window.location.href = response.payment_url;
            } else {
                // Mostrar confirmación
                this.showSuccess('Reserva creada exitosamente');
                this.$form[0].reset();
                this.selectedDate = null;
                this.selectedTime = null;
                this.updateCalendar();
            }
        },

        /**
         * Validar email
         */
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Formatear fecha
         */
        formatDate: function(date, format) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            if (format === 'YYYY-MM-DD') {
                return year + '-' + month + '-' + day;
            }
            return date.toLocaleDateString();
        },

        /**
         * Obtener mes y año
         */
        getMonthYear: function(date) {
            const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            return months[date.getMonth()] + ' ' + date.getFullYear();
        },

        /**
         * Mostrar error
         */
        showError: function(message) {
            this.showNotification(message, 'error');
        },

        /**
         * Mostrar éxito
         */
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },

        /**
         * Mostrar carga
         */
        showLoading: function(message) {
            this.showNotification(message, 'loading');
        },

        /**
         * Mostrar notificación
         */
        showNotification: function(message, type) {
            const $notification = $('<div class="pbs-notification pbs-' + type + '">' + message + '</div>');
            this.$container.prepend($notification);

            if (type !== 'loading') {
                setTimeout(function() {
                    $notification.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }
    };

    /**
     * Inicializar cuando document esté listo
     */
    $(document).ready(function() {
        if ($('.pbs-booking-form').length > 0) {
            PBS.init();
        }
    });

    // Exportar objeto PBS para acceso global
    window.PBS = PBS;

})(jQuery);
