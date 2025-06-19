// JavaScript to handle avatar selection
document.querySelectorAll('.avatar').forEach((avatar) => {
    avatar.addEventListener('click', function () {
        document.querySelectorAll('.avatar').forEach((el) => el.classList.remove('selected'));
        this.classList.add('selected');
    });
});
