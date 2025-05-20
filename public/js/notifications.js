// Update unread notifications count every 30 seconds
setInterval(() => {
    fetch('/api/notifications/unread_count.php')
        .then(r => r.json())
        .then(d => {
            const badge = document.getElementById('unread-count');
            if (badge) {
                badge.textContent = d.unread_count;
                badge.style.display = d.unread_count > 0 ? 'inline' : 'none';
            }
        })
        .catch(err => console.error('Error fetching notifications:', err));
}, 30000); 