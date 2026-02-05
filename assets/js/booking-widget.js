(function($) {
    'use strict';

    class PBSBookingWidget {
        constructor(element) {
            this.$widget = $(element);
            this.serviceId = this.$widget.data('service-id');
            this.selectedDate = null;
            this.selectedTime = null;
            this.currentMonth = new Date();
            this.csrfToken = null;
            
            this.init();
        }

        init() {
            this.loadServiceInfo();
            this.renderCalendar();
            this.bindEvents();
        }

        bindEvents() {
            const self = this;

            // Navegación del calendario
            this.$widget.on('click', '.pbs-calendar-prev', function() {
                self.currentMonth.setMonth(self.currentMonth.getMonth() - 1);
                self.renderCalendar();
            });

            this.$widget.on('click', '.pbs-calendar-next', function() {
                self.currentMonth.setMonth(self.currentMonth.getMonth() + 1);
                self.renderCalendar();
            });

            // Selección de día
            this.$widget.on('click', '.pbs-calendar-day:not(.disabled)', function() {
                const date = $(this).data('date');
                self.selectDate(date);
            });

            // Selección de hora
            this.$widget.on('click', '.pbs-time-slot', function() {
                self.$widget.find('.pbs-time-slot').removeClass('selected');
                $(this).addClass('selected');
                self.selectedTime = $(this).data('time');
                self.$widget.find('.pbs-form-section').slideDown();
            });

            // Envío del formulario
            this.$widget.on('submit', '.pbs-booking-form', function(e) {
                e.preventDefault();
                self.submitBooking();
            });
        }

        loadServiceInfo() {
            const self = this;
            const $info = this.$widget.find('.pbs-service-info');

            if ($info.length === 0) return;

            $.ajax({
                url: pbsBooking.apiUrl + '/services/' + this.serviceId,
                method: 'GET',
                success: function(service) {
                    $info.html(`
                        <h3>${service.name}</h3>
                        <p>${service.description}</p>
                        <p><strong>${pbsBooking.i18n.duration || 'Duration'}:</strong> ${service.duration} min</p>
                        <p><strong>${pbsBooking.i18n.price || 'Price'}:</strong> ${service.price} ${service.currency}</p>
                    `);
                },
                error: function(xhr, status, error) {
                    console.error('Service API Error:', status, error, xhr.responseText);
                    $info.html('<p>Error loading service info</p>');
                }
            });
        }

        renderCalendar() {
            const year = this.currentMonth.getFullYear();
            const month = this.currentMonth.getMonth();
            
            // Actualizar encabezado
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];
            this.$widget.find('.pbs-calendar-month').text(`${monthNames[month]} ${year}`);

            // Calcular días
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDay = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1; // Lunes = 0
            
            const $daysContainer = this.$widget.find('.pbs-calendar-days');
            $daysContainer.empty();

            // Días vacíos al inicio
            for (let i = 0; i < startDay; i++) {
                $daysContainer.append('<div class="pbs-calendar-day disabled"></div>');
            }

            // Días del mes
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            for (let day = 1; day <= lastDay.getDate(); day++) {
                const date = new Date(year, month, day);
                const dateStr = this.formatDate(date);
                const isPast = date < today;
                
                const $day = $('<div class="pbs-calendar-day"></div>')
                    .text(day)
                    .data('date', dateStr);

                if (isPast) {
                    $day.addClass('disabled');
                }

                $daysContainer.append($day);
            }
        }

        selectDate(date) {
            this.selectedDate = date;
            this.$widget.find('.pbs-calendar-day').removeClass('selected');
            this.$widget.find(`.pbs-calendar-day[data-date="${date}"]`).addClass('selected');
            
            this.loadTimeSlots(date);
        }

        loadTimeSlots(date) {
            const self = this;
            const $timeSection = this.$widget.find('.pbs-time-section');
            const $slotsContainer = this.$widget.find('.pbs-time-slots');

            $slotsContainer.html('<p>' + pbsBooking.i18n.loading + '</p>');
            $timeSection.slideDown();

            $.ajax({
                url: pbsBooking.apiUrl + '/availability/day',
                method: 'GET',
                data: {
                    service_id: this.serviceId,
                    date: date
                },
                success: function(response) {
                    if (response.slots && response.slots.length > 0) {
                        $slotsContainer.empty();
                        response.slots.forEach(function(slot) {
                            const $slot = $('<div class="pbs-time-slot"></div>')
                                .text(slot.start + ' - ' + slot.end)
                                .data('time', slot.start);
                            $slotsContainer.append($slot);
                        });
                    } else {
                        $slotsContainer.html('<p>' + pbsBooking.i18n.noSlots + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Availability API Error:', status, error, xhr.responseText);
                    $slotsContainer.html('<p>Error loading time slots</p>');
                }
            });
        }

        submitBooking() {
            const self = this;
            const $form = this.$widget.find('.pbs-booking-form');
            const $message = this.$widget.find('.pbs-message');
            const $btn = this.$widget.find('.pbs-submit-btn');

            // Evitar múltiples clics
            if ($btn.prop('disabled')) {
                console.log('Button already disabled, ignoring duplicate click');
                return;
            }

            // Validación básica
            const name = $form.find('[name="name"]').val().trim();
            const email = $form.find('[name="email"]').val().trim();

            if (!name || !email) {
                this.showMessage(pbsBooking.i18n.fillAllFields, 'error');
                return;
            }

            if (!this.validateEmail(email)) {
                this.showMessage(pbsBooking.i18n.invalidEmail, 'error');
                return;
            }

            if (!this.selectedDate || !this.selectedTime) {
                this.showMessage('Please select date and time', 'error');
                return;
            }

            // Deshabilitar botón inmediatamente
            $btn.prop('disabled', true).text(pbsBooking.i18n.loading);

            // Asegurar CSRF token antes de enviar
            if (!this.csrfToken) {
                console.log('Fetching CSRF token before booking...');
                $.ajax({
                    url: pbsBooking.apiUrl + '/csrf-token',
                    method: 'GET',
                    success: function(response) {
                        if (response.csrf_token) {
                            self.csrfToken = response.csrf_token;
                            console.log('CSRF token obtained, proceeding with booking');
                            self.sendBookingRequest($form, $btn);
                        } else {
                            self.showMessage('Security token error. Please try again.', 'error');
                            $btn.prop('disabled', false).text(pbsBooking.i18n.confirmBooking || 'Confirm Booking');
                        }
                    },
                    error: function() {
                        self.showMessage('Security token error. Please try again.', 'error');
                        $btn.prop('disabled', false).text(pbsBooking.i18n.confirmBooking || 'Confirm Booking');
                    }
                });
                return;
            }

            this.sendBookingRequest($form, $btn);
        }

        sendBookingRequest($form, $btn) {
            const self = this;
            const name = $form.find('[name="name"]').val().trim();
            const email = $form.find('[name="email"]').val().trim();

            const data = {
                service_id: this.serviceId,
                name: name,
                email: email,
                phone: $form.find('[name="phone"]').val(),
                date: this.selectedDate,
                time: this.selectedTime,
                notes: $form.find('[name="notes"]').val(),
                nonce: pbsBooking.nonce,
                csrf_token: pbsBooking.csrfToken || this.csrfToken
            };

            $.ajax({
                url: pbsBooking.apiUrl + '/bookings/create',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': pbsBooking.nonce,
                    'X-CSRF-Token': pbsBooking.csrfToken || this.csrfToken
                },
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify(data),
                statusCode: {
                    201: function(response) {
                        const booking = response.booking;
                        console.log('Booking created successfully:', booking);

                        // Decidir según proveedor de pago
                        if (pbsBooking.payment && pbsBooking.payment.provider === 'mercadopago') {
                            // MercadoPago
                            $.ajax({
                                url: pbsBooking.apiUrl + '/payments/mercadopago/create_preference',
                                method: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ booking_id: booking.id }),
                                success: function(payResponse) {
                                    if (payResponse.redirect_url) {
                                        window.location.href = payResponse.redirect_url;
                                    } else {
                                        self.showMessage('Error creating MercadoPago payment', 'error');
                                        $btn.prop('disabled', false).text('Confirm Booking');
                                    }
                                },
                                error: function() {
                                    self.showMessage('Error creating MercadoPago payment', 'error');
                                    $btn.prop('disabled', false).text('Confirm Booking');
                                }
                            });
                        } else if (pbsBooking.payment && pbsBooking.payment.provider === 'stripe') {
                            // Stripe
                            $.ajax({
                                url: pbsBooking.apiUrl + '/payments/stripe/create_session',
                                method: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ booking_id: booking.id }),
                                success: function(payResponse) {
                                    if (payResponse.session_id) {
                                        self.redirectToStripe(pbsBooking.payment.public_key, payResponse.session_id);
                                    } else {
                                        self.showMessage('Error creating Stripe session', 'error');
                                        $btn.prop('disabled', false).text('Confirm Booking');
                                    }
                                },
                                error: function() {
                                    self.showMessage('Error creating Stripe session', 'error');
                                    $btn.prop('disabled', false).text('Confirm Booking');
                                }
                            });
                        } else if (pbsBooking.payment && pbsBooking.payment.provider === 'paypal') {
                            // PayPal
                            $.ajax({
                                url: pbsBooking.apiUrl + '/payments/paypal/create_order',
                                method: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ booking_id: booking.id }),
                                success: function(payResponse) {
                                    if (payResponse.approve_url) {
                                        window.location.href = payResponse.approve_url;
                                    } else {
                                        self.showMessage('Error creating PayPal order', 'error');
                                        $btn.prop('disabled', false).text('Confirm Booking');
                                    }
                                },
                                error: function() {
                                    self.showMessage('Error creating PayPal order', 'error');
                                    $btn.prop('disabled', false).text('Confirm Booking');
                                }
                            });

                        } else {
                            // Sin pago online
                            self.showMessage(pbsBooking.i18n.bookingSuccess, 'success');
                            $form[0].reset();
                            self.$widget.find('.pbs-time-section, .pbs-form-section').slideUp();
                            self.renderCalendar();
                            $btn.prop('disabled', false).text('Confirm Booking');
                        }
                    }
                },
                success: function(response) {
                    // HTTP 200-299 (except 201 which is handled above)
                    console.log('Booking response:', response);
                },
                error: function(xhr) {
                    console.log('Booking error - Status:', xhr.status, 'Response:', xhr.responseJSON);
                    const message = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : pbsBooking.i18n.bookingError;
                    self.showMessage(message, 'error');
                    $btn.prop('disabled', false).text('Confirm Booking');
                },

            });
        }

        redirectToStripe(publicKey, sessionId) {
            const stripe = Stripe(publicKey);
            stripe.redirectToCheckout({ sessionId: sessionId }).then(function(result) {
                if (result.error) {
                    alert(result.error.message);
                }
            });
        }
        showMessage(text, type) {
            const $message = this.$widget.find('.pbs-message');
            $message
                .removeClass('success error')
                .addClass(type)
                .html(text)
                .slideDown();

            setTimeout(function() {
                $message.slideUp();
            }, 5000);
        }

        validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    }

    // Inicializar widgets
    $(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/pbs_booking.default', function($scope) {
            new PBSBookingWidget($scope.find('.pbs-booking-widget'));
        });
    });

    // Fallback para shortcodes o uso fuera de Elementor
    $(document).ready(function() {
        $('.pbs-booking-widget').each(function() {
            new PBSBookingWidget(this);
        });
    });

})(jQuery);