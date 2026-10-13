document.addEventListener('DOMContentLoaded', function () {
    const nutMenu = document.getElementById('nutMenuDiDong');
    const hopMenu = document.getElementById('hopMenuDiDong');

    if (nutMenu && hopMenu) {
        nutMenu.addEventListener('click', function () {
            hopMenu.classList.toggle('hidden');
        });
    }
});