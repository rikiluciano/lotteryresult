function updateRLabsYear() {
    const yearElement = document.getElementById('current-year');
    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initial attempt (for static inclusion)
    updateRLabsYear();

    // 2. Add subtle interaction to brand mesh on mouse move
    const footer = document.querySelector('.rlabs-footer');
    const mesh = document.querySelector('.footer-glass-mesh');

    if (footer && mesh) {
        footer.addEventListener('mousemove', (e) => {
            const { clientX, clientY } = e;
            const x = (clientX / window.innerWidth) * 20;
            const y = (clientY / window.innerHeight) * 20;

            mesh.style.transform = `translate(${x}px, ${y}px)`;
        });
    }

    // 3. Optional: Intelligent system status message
    const statusText = document.querySelector('.status-text');
    if (statusText) {
        const hour = new Date().getHours();
        if (hour > 22 || hour < 5) {
            statusText.textContent = 'Modo Nocturno Activo';
        }
    }
});
