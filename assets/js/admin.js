jQuery(document).ready(function($) {
    'use strict';

    // ========================================
    // SERVICIOS
    // ========================================

    // Abrir modal para agregar servicio
    $('#pbs-add-service-btn').on('click', function() {
        $('#pbs-service-modal-title').text('Agregar Servicio');
        $('#pbs-service-form')[0].reset();
        $('#pbs-service-id').val('');
        $('#pbs-service-modal').fadeIn();
    });

    // Editar servicio
    $(document).on('click', '.pbs-edit-service', function() {
        var serviceId = $(this).data('id');
        
        // Aquí deberías hacer un AJAX para obtener los datos del servicio
        // Por simplicidad, puedes implementarlo después
        alert('Función de edición: implementar AJAX para cargar datos del servicio #' + serviceId);
    });

    // Eliminar servicio
    $(document).on('click', '.pbs-delete-service', function() {
        if (!confirm('¿Estás seguro de eliminar este servicio?')) {
            return;
        }

        var serviceId = $(this).data('id');
        var $button = $(this);

        $.ajax({
            url: pbsAdminData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pbs_delete_service',
                nonce: pbsAdminData.nonce,
                service_id: serviceId
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Eliminando...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false).text('Eliminar');
                }
            },
            error: function() {
                alert('Error al eliminar el servicio');
                $button.prop('disabled', false).text('Eliminar');
            }
        });
    });

    // Guardar servicio
    $('#pbs-service-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        formData += '&action=pbs_save_service&nonce=' + pbsAdminData.nonce;

        $.ajax({
            url: pbsAdminData.ajaxUrl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#pbs-service-form button[type="submit"]').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    $('#pbs-service-form button[type="submit"]').prop('disabled', false).text('Guardar');
                }
            },
            error: function() {
                alert('Error al guardar el servicio');
                $('#pbs-service-form button[type="submit"]').prop('disabled', false).text('Guardar');
            }
        });
    });

    // ========================================
    // HORARIOS
    // ========================================

    // Abrir modal para agregar horario
    $('#pbs-add-schedule-btn').on('click', function() {
        $('#pbs-schedule-form')[0].reset();
        $('#pbs-schedule-modal').fadeIn();
    });

    // Eliminar horario
    $(document).on('click', '.pbs-delete-schedule', function() {
        if (!confirm('¿Estás seguro de eliminar este horario?')) {
            return;
        }

        var scheduleId = $(this).data('id');
        var $button = $(this);

        $.ajax({
            url: pbsAdminData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pbs_delete_schedule',
                nonce: pbsAdminData.nonce,
                schedule_id: scheduleId
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Eliminando...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false).text('Eliminar');
                }
            },
            error: function() {
                alert('Error al eliminar el horario');
                $button.prop('disabled', false).text('Eliminar');
            }
        });
    });

    // Guardar horario
    $('#pbs-schedule-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        formData += '&action=pbs_save_schedule&nonce=' + pbsAdminData.nonce;

        $.ajax({
            url: pbsAdminData.ajaxUrl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#pbs-schedule-form button[type="submit"]').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    $('#pbs-schedule-form button[type="submit"]').prop('disabled', false).text('Guardar');
                }
            },
            error: function() {
                alert('Error al guardar el horario');
                $('#pbs-schedule-form button[type="submit"]').prop('disabled', false).text('Guardar');
            }
        });
    });

    // ========================================
    // EXCEPCIONES
    // ========================================

    // Abrir modal para agregar excepción
    $('#pbs-add-exception-btn').on('click', function() {
        $('#pbs-exception-form')[0].reset();
        $('#pbs-exception-time-range').hide();
        $('#pbs-exception-modal').fadeIn();
    });

    // Toggle de horario completo/parcial en excepciones
    $('#pbs-exception-fullday').on('change', function() {
        if ($(this).is(':checked')) {
            $('#pbs-exception-time-range').hide();
            $('#pbs-exception-time-range input').prop('required', false);
        } else {
            $('#pbs-exception-time-range').show();
            $('#pbs-exception-time-range input').prop('required', true);
        }
    });

    // Guardar excepción
    $('#pbs-exception-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        formData += '&action=pbs_save_exception&nonce=' + pbsAdminData.nonce;

        $.ajax({
            url: pbsAdminData.ajaxUrl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#pbs-exception-form button[type="submit"]').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    $('#pbs-exception-form button[type="submit"]').prop('disabled', false).text('Guardar');
                }
            },
            error: function() {
                alert('Error al guardar la excepción');
                $('#pbs-exception-form button[type="submit"]').prop('disabled', false).text('Guardar');
            }
        });
    });

    // ========================================
    // RESERVAS
    // ========================================

    // Cambiar estado de reserva
    $(document).on('change', '.pbs-booking-status-change', function() {
        var $select = $(this);
        var bookingId = $select.data('booking-id');
        var newStatus = $select.val();

        if (!newStatus) {
            return;
        }

        if (!confirm('¿Cambiar el estado de esta reserva?')) {
            $select.val('');
            return;
        }

        $.ajax({
            url: pbsAdminData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pbs_update_booking_status',
                nonce: pbsAdminData.nonce,
                booking_id: bookingId,
                status: newStatus
            },
            beforeSend: function() {
                $select.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                    $select.prop('disabled', false).val('');
                }
            },
            error: function() {
                alert('Error al actualizar el estado');
                $select.prop('disabled', false).val('');
            }
        });
    });

    // ========================================
    // MODALES
    // ========================================

    // Cerrar modal
    $('.pbs-modal-close').on('click', function() {
        $(this).closest('.pbs-modal').fadeOut();
    });

    // Cerrar modal al hacer clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('pbs-modal')) {
            $('.pbs-modal').fadeOut();
        }
    });


    
    const nonce = pbsAdminData.nonce; // asumiendo que ya localizaste este objeto

    // Ver detalle
    $(document).on('click', '.pbs-view-booking', function(e) {
        e.preventDefault();
        const bookingId = $(this).data('booking-id');

        $.post(ajaxurl, {
            action: 'pbs_get_booking_detail',
            nonce: nonce,
            booking_id: bookingId
        }, function(response) {
            if (response.success) {
                $('#pbs-booking-detail-modal .pbs-booking-detail-content').html(response.data.html);
                $('#pbs-booking-detail-modal').fadeIn(200);
            } else {
                alert(response.data && response.data.message ? response.data.message : 'Error loading booking detail');
            }
        });
    });

    // Cerrar modal
    $(document).on('click', '.pbs-modal-close, .pbs-modal-close-btn', function() {
        $(this).closest('.pbs-modal').fadeOut(200);
    });

    // Confirmar reserva (acción rápida)
    $(document).on('click', '.pbs-confirm-booking', function(e) {
        e.preventDefault();
        if (!confirm('¿Confirmar esta reserva?')) return;
        const $row = $(this).closest('tr');
        const bookingId = $(this).data('booking-id');

        $.post(ajaxurl, {
            action: 'pbs_update_booking_status',
            nonce: nonce,
            booking_id: bookingId,
            status: 'confirmed'
        }, function(response) {
            if (response.success) {
                location.reload(); // sencillo; si quieres, puedes actualizar solo la fila.
            } else {
                alert(response.data && response.data.message ? response.data.message : 'Error updating booking');
            }
        });
    });

    // Cancelar reserva (acción rápida)
    $(document).on('click', '.pbs-cancel-booking', function(e) {
        e.preventDefault();
        if (!confirm('¿Cancelar esta reserva?')) return;
        const bookingId = $(this).data('booking-id');

        $.post(ajaxurl, {
            action: 'pbs_update_booking_status',
            nonce: nonce,
            booking_id: bookingId,
            status: 'cancelled'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data && response.data.message ? response.data.message : 'Error updating booking');
            }
        });
    });

    // Generar Google Meet link
    $(document).on('click', '.pbs-generate-meet', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const bookingId = $btn.data('booking-id');
        const originalText = $btn.text();
        
        console.log('Generate Meet clicked for booking ID:', bookingId);
        console.log('Nonce:', nonce);
        console.log('AJAX URL:', ajaxurl);
        
        $btn.prop('disabled', true).text('Generando...');

        $.post(ajaxurl, {
            action: 'pbs_generate_meet',
            nonce: nonce,
            booking_id: bookingId
        }, function(response) {
            console.log('Response:', response);
            if (response.success) {
                alert('✅ Google Meet link generado correctamente!\n\nLink: ' + response.data.meet_link);
                location.reload();
            } else {
                const errorMsg = response.data && response.data.message ? response.data.message : 'Error generating Google Meet link';
                
                // Mostrar mensaje específico si Google Calendar no está habilitado
                if (errorMsg.includes('no está habilitado')) {
                    alert('⚠️ Google Calendar no está habilitado\n\nPor favor, ve a:\nReservas → Configuración → Google Calendar\n\ny configura tus credenciales de Google.');
                } else {
                    alert('❌ Error: ' + errorMsg);
                }
                
                $btn.prop('disabled', false).text(originalText);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            alert('Error: ' + xhr.status + ' ' + xhr.statusText);
            $btn.prop('disabled', false).text(originalText);
        });
    });
});