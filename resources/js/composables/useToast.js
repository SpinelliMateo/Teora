// composables/useToast.js
import toastr from "toastr";
import 'toastr/build/toastr.min.css';

export const useToast = () => {
    // Configuración optimizada de toastr
    const initToastr = () => {
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: true,
            progressBar: true,
            positionClass: "toast-bottom-right",
            preventDuplicates: true,
            onclick: null,
            showDuration: "200",
            hideDuration: "800",
            timeOut: "4000",
            extendedTimeOut: "1000",
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut"
        };
    };

    // Métodos para diferentes tipos de notificaciones
    const success = (message, title = 'Éxito') => {
        initToastr();
        toastr.success(message, title);
    };

    const error = (message, title = 'Error') => {
        initToastr();
        toastr.error(message, title);
    };

    const warning = (message, title = 'Advertencia') => {
        initToastr();
        toastr.warning(message, title);
    };

    const info = (message, title = 'Información') => {
        initToastr();
        toastr.info(message, title);
    };

    // Método para manejar flash messages de Laravel automáticamente
    const handleFlash = (flash) => {
        if (!flash) return;

        if (flash.success) {
            success(flash.success, 'Actualizado');
        }
        
        if (flash.error) {
            error(flash.error, 'Error');
        }
        
        if (flash.warning) {
            warning(flash.warning, 'Advertencia');
        }
        
        if (flash.info) {
            info(flash.info, 'Información');
        }

        // Para errores de validación
        if (flash.errors) {
            const errorMessages = Object.values(flash.errors).flat();
            errorMessages.forEach(msg => error(msg, 'Error de validación'));
        }
    };

    // Método para limpiar todas las notificaciones
    const clear = () => {
        toastr.clear();
    };

    // Configuración personalizada temporal
    const customToast = (message, title, type = 'info', options = {}) => {
        const originalOptions = { ...toastr.options };
        
        toastr.options = {
            ...toastr.options,
            ...options
        };
        
        toastr[type](message, title);
        
        // Restaurar configuración original
        toastr.options = originalOptions;
    };

    return {
        success,
        error,
        warning,
        info,
        handleFlash,
        clear,
        customToast
    };
};