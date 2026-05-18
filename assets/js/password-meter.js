document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    if (!passwordInput) return;
    
    function checkStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score++;
        if (password.match(/[a-z]/)) score++;
        if (password.match(/[A-Z]/)) score++;
        if (password.match(/[0-9]/)) score++;
        if (password.match(/[^a-zA-Z0-9]/)) score++;
        
        return score;
    }
    
    function updateStrengthMeter() {
        const password = passwordInput.value;
        const score = checkStrength(password);
        
        let width = 0;
        let color = '';
        let text = '';
        
        if (score <= 2) {
            width = 20;
            color = '#dc3545';
            text = 'Débil';
        } else if (score <= 4) {
            width = 60;
            color = '#ffc107';
            text = 'Media';
        } else {
            width = 100;
            color = '#28a745';
            text = 'Fuerte';
        }
        
        if (password.length === 0) {
            width = 0;
            text = '';
        }
        
        strengthBar.style.width = width + '%';
        strengthBar.style.backgroundColor = color;
        strengthText.textContent = text;
        strengthText.style.color = color;
    }
    
    passwordInput.addEventListener('input', updateStrengthMeter);
});