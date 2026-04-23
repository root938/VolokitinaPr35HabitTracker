// Interactive/CreateHabit.js

function limitAndNext(el) {
    if (el.value.length > 1) el.value = el.value.slice(0, 1);
    if (el.value.length === 1) {
        const inputs = Array.from(document.querySelectorAll('.digit input'));
        const index = inputs.indexOf(el);
        if (index < inputs.length - 1) inputs[index + 1].focus();
    }
}

function showError(msg) {
    document.getElementById('error-text').innerText = msg;
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('error-modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('error-modal').style.display = 'none';
}

document.getElementById('habitForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const h1 = document.getElementsByName('h1')[0].value;
    const h2 = document.getElementsByName('h2')[0].value;
    const m1 = document.getElementsByName('m1')[0].value;
    const m2 = document.getElementsByName('m2')[0].value;

    const hours = parseInt(h1 + h2);
    const minutes = parseInt(m1 + m2);
    const title = document.getElementsByName('title')[0].value.trim();

    // Валидация
    if (title.length < 2) return showError("Название слишком короткое!");
    if (isNaN(hours) || hours > 23) return showError("Часы: укажите от 00 до 23");
    if (isNaN(minutes) || minutes > 59) return showError("Минуты: укажите от 00 до 59");

    const formData = new FormData(this);
    // Формируем формат времени HH:MM:SS для базы
    const timeFormatted = `${h1}${h2}:${m1}${m2}:00`;
    formData.append('reminder_time', timeFormatted);

    try {
        const response = await fetch('Logic/SaveHabit.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            window.location.href = 'Index.php';
        } else {
            showError(result.message);
        }
    } catch (err) {
        showError("Ошибка сервера. Проверьте соединение.");
    }
});