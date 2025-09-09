function checkNotifications() {
    $.ajax({
        url: 'check_notifications.php',
        method: 'GET',
        success: function(response) {
            if (response) {
                alert('Ada notifikasi baru!');
            }
        }
    });
}

// Polling setiap 5 detik
setInterval(checkNotifications, 5000);