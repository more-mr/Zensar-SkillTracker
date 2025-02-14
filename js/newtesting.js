// JavaScript for message popup
document.querySelectorAll('.message-icon').forEach(icon => {
    icon.addEventListener('click', function(event) {
        event.preventDefault();
        const userId = this.getAttribute('data-user-id');
        document.getElementById('messagePopup').style.display = 'block';
        document.getElementById('sendMessageBtn').setAttribute('data-user-id', userId);
    });
});

document.querySelector('.close-btn').addEventListener('click', function() {
    document.getElementById('messagePopup').style.display = 'none';
});

document.getElementById('sendMessageBtn').addEventListener('click', function() {
    const userId = this.getAttribute('data-user-id');
    const message = document.getElementById('messageContent').value;
    
    if (message.trim() !== '') {
        // Send feedback via AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert('Message sent to user ID ' + userId);
                document.getElementById('messagePopup').style.display = 'none';
                document.getElementById('messageContent').value = '';
            } else {
                alert('Failed to send message.');
            }
        };
        xhr.send('submit_feedback=1&user_id=' + userId + '&feedback=' + encodeURIComponent(message));
    } else {
        alert('Message cannot be empty.');
    }
});

// JavaScript for delete confirmation
function confirmDelete(userId) {
    if (confirm("Are you sure you want to delete this user?")) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '?delete=' + userId, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                location.reload(); // Refresh the page after successful deletion
            } else {
                alert('Failed to delete user.');
            }
        };
        xhr.send();
    }
    return false;
}

// JavaScript for select all checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    document.querySelector('.actions').style.display = this.checked ? 'block' : 'none';
});

// JavaScript for individual checkbox handling
document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const anyChecked = document.querySelectorAll('.user-checkbox:checked').length > 0;
        document.querySelector('.actions').style.display = anyChecked ? 'block' : 'none';
    });
});