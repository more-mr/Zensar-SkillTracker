
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const forgotPassword = document.getElementById('forgotPassword');
    const forgotPasswordModal = document.getElementById('forgotPasswordModal');
    const closeModal = document.getElementsByClassName('close')[0];
    const cancelForgotPassword = document.getElementById('cancelForgotPassword');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.textContent = type === 'password' ? '👁️' : '🔒';
    });

    forgotPassword.addEventListener('click', function () {
        forgotPasswordModal.style.display = 'flex';
    });

    closeModal.addEventListener('click', function () {
        forgotPasswordModal.style.display = 'none';
    });

    cancelForgotPassword.addEventListener('click', function () {
        forgotPasswordModal.style.display = 'none';
    });

    window.addEventListener('click', function (event) {
        if (event.target === forgotPasswordModal) {
            forgotPasswordModal.style.display = 'none';
        }
    });
});