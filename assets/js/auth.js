document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario de registro
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            // Verificar fortaleza mínima
            const strength = checkPasswordStrength(password);
            if (strength < 3) {
                e.preventDefault();
                alert('La contraseña es demasiado débil. Usa al menos 8 caracteres con mayúsculas, minúsculas, números y símbolos.');
                return false;
            }
        });
    }
    
    function checkPasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (password.match(/[a-z]/)) score++;
        if (password.match(/[A-Z]/)) score++;
        if (password.match(/[0-9]/)) score++;
        if (password.match(/[^a-zA-Z0-9]/)) score++;
        return score;
    }
});