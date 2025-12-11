<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WebSystem</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" referrerpolicy="no-referrer" />
  <link href="https://unpkg.com/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
  <?= $this->renderSection('styles') ?>
</head>
<body class="<?= $this->renderSection('body_class') ?>">
<?php $uri = service('uri'); ?>
<?= $this->include('templates/header') ?>

<?= $this->renderSection('content') ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" referrerpolicy="no-referrer"></script>
<?= $this->renderSection('scripts') ?>
<script>
$(document).ready(function() {
    // Function to fetch and update notifications
    function fetchNotifications() {
        $.get('<?= site_url('/notifications') ?>')
            .done(function(response) {
                if (response.success) {
                    updateNotificationBadge(response.unread_count);
                    updateNotificationList(response.notifications);
                }
            })
            .fail(function() {
                console.error('Failed to fetch notifications');
            });
    }

    // Update notification badge
    function updateNotificationBadge(count) {
        const badge = $('#notificationBadge');
        if (count > 0) {
            badge.text(count).show();
        } else {
            badge.hide();
        }
    }

    // Update notification list
    function updateNotificationList(notifications) {
        const list = $('#notificationList');
        list.empty();

        // Filter out read notifications - only show unread
        const unreadNotifications = notifications.filter(function(notification) {
            return notification.is_read == 0;
        });

        if (unreadNotifications.length === 0) {
            list.html('<div class="px-3 py-2 text-muted text-center"><small>No notifications</small></div>');
            return;
        }

        unreadNotifications.forEach(function(notification) {
            const notificationItem = $('<div>')
                .addClass('px-3 py-2 alert-info notification-item')
                .attr('data-notification-id', notification.id)
                .html(
                    '<div class="d-flex justify-content-between align-items-start">' +
                    '<div class="flex-grow-1">' +
                    '<small>' + escapeHtml(notification.message) + '</small><br>' +
                    '<small class="text-muted">' + formatDate(notification.created_at) + '</small>' +
                    '</div>' +
                    '<button class="btn btn-sm btn-outline-primary ms-2 mark-read-btn" data-id="' + notification.id + '">Mark as Read</button>' +
                    '</div>'
                );
            
            list.append(notificationItem);
        });

        // Attach click handlers to "Mark as Read" buttons
        $('.mark-read-btn').on('click', function() {
            const notificationId = $(this).data('id');
            markAsRead(notificationId, $(this).closest('.notification-item'));
        });
    }

    // Mark notification as read
    function markAsRead(notificationId, notificationElement) {
        $.post('<?= site_url('/notifications/mark_read') ?>/' + notificationId)
            .done(function(response) {
                if (response.success) {
                    // Immediately remove the notification from the list
                    if (notificationElement) {
                        notificationElement.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if there are no more notifications
                            const list = $('#notificationList');
                            if (list.children('.notification-item').length === 0) {
                                list.html('<div class="px-3 py-2 text-muted text-center"><small>No notifications</small></div>');
                            }
                        });
                    }
                    
                    // Refresh notifications to update badge count
                    fetchNotifications();
                } else {
                    alert('Failed to mark notification as read: ' + (response.message || 'Unknown error'));
                }
            })
            .fail(function() {
                alert('Failed to mark notification as read');
            });
    }

    // Format date for display
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) {
            return 'Just now';
        } else if (diffMins < 60) {
            return diffMins + ' minute' + (diffMins > 1 ? 's' : '') + ' ago';
        } else if (diffHours < 24) {
            return diffHours + ' hour' + (diffHours > 1 ? 's' : '') + ' ago';
        } else if (diffDays < 7) {
            return diffDays + ' day' + (diffDays > 1 ? 's' : '') + ' ago';
        } else {
            return date.toLocaleDateString();
        }
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Initial fetch
    fetchNotifications();

    // Optional: Refresh notifications every 60 seconds
    setInterval(fetchNotifications, 60000);
});
</script>
</body>
</html>