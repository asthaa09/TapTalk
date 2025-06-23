document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.sidebar nav a');
    const content = document.querySelector('.content');
    const defaultPage = 'home';
    let chatInterval; // Keep track of the interval

    function loadPage(page, params = {}) {
        // Clear any existing chat polling interval
        if (chatInterval) {
            clearInterval(chatInterval);
        }

        // Remove active class from all nav links
        navLinks.forEach(link => {
            link.parentElement.classList.remove('active');
        });

        // Add active class to the current page link
        const activeLink = document.querySelector(`.sidebar nav [data-page=${page}] a`);
        if (activeLink) {
            activeLink.parentElement.classList.add('active');
        }
        
        let url = `api/get_${page}.php`;
        if (params) {
            const query = new URLSearchParams(params).toString();
            if(query) url += `?${query}`;
        }

        fetch(url)
            .then(response => response.text())
            .then(data => {
                content.innerHTML = data;
                
                // If we loaded the chat page, start polling for new messages
                if (page === 'chat') {
                    const chatArea = content.querySelector('.chat-area');
                    const chatPartnerId = chatArea.dataset.chatWith;
                    if (chatPartnerId) {
                        chatInterval = setInterval(() => fetchNewMessages(chatPartnerId), 3000);
                    }
                     // Scroll to the bottom of the messages
                    const messagesContainer = content.querySelector('.chat-messages');
                    if(messagesContainer) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }
            })
            .catch(error => {
                content.innerHTML = '<p>Error loading page.</p>';
                console.error('Error:', error);
            });
    }

    function fetchNewMessages(chatPartnerId) {
        const messagesContainer = document.querySelector('.chat-messages');
        const lastMessage = messagesContainer.lastElementChild;
        // The message ID should be stored in a data attribute on the message element.
        // Let's modify the get_chat.php and this function to handle that.
        // For now, let's assume we reload the whole chat. A more optimized solution would pass the last message ID.
        // Let's modify get_chat.php and get_messages.php to add the id
        const lastId = lastMessage ? lastMessage.dataset.messageId : 0;
        
        fetch(`api/get_messages.php?chat_with=${chatPartnerId}&last_id=${lastId}`)
        .then(response => response.json())
        .then(data => {
            if(data.success && data.messages.length > 0) {
                data.messages.forEach(message => {
                     const newMessage = document.createElement('div');
                     // Assuming the server returns the sender_id to check if it's a 'self' message
                     // We need the current user's ID. Let's add it to the body or a global JS var.
                     // For now, let's assume we can't know who sent it. This is a simplification.
                     // The proper way is to have the user_id available in JS.
                     newMessage.className = message.sender_id == document.body.dataset.userId ? 'message self' : 'message';
                     newMessage.dataset.messageId = message.id;
                     newMessage.innerHTML = `<p>${message.message}</p><small>${new Date(message.created_at).toLocaleTimeString()}</small>`;
                     messagesContainer.appendChild(newMessage);
                });
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        })
        .catch(console.error);
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.parentElement.dataset.page;
            loadPage(page);
        });
    });

    // Event delegation for dynamic content
    content.addEventListener('click', function(e) {
        const target = e.target;
        const userCardActions = target.closest('.user-card-actions');
        if (!userCardActions) return;

        const friendId = userCardActions.dataset.userId;

        if (target.classList.contains('add-friend')) {
            handleFriendAction('api/friend_request.php', friendId, userCardActions, 'Request Sent');
        } else if (target.classList.contains('accept-request')) {
            handleFriendAction('api/accept_request.php', friendId, userCardActions, 'Accepted');
        } else if (target.classList.contains('reject-request')) {
            handleFriendAction('api/reject_request.php', friendId, userCardActions, 'Rejected');
        }

        // Handle sending a chat message
        if (target.matches('.chat-input button')) {
            const chatArea = target.closest('.chat-area');
            const chatInput = chatArea.querySelector('input');
            const message = chatInput.value.trim();
            const receiverId = chatArea.dataset.chatWith;

            console.log('Sending message:', { message, receiverId }); // Debug log

            if (message && receiverId) {
                const formData = new FormData();
                formData.append('receiver_id', receiverId);
                formData.append('message', message);

                fetch('api/send_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status); // Debug log
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data); // Debug log
                    if (data.success) {
                        chatInput.value = '';
                        // Instantly add the message to the UI for better UX
                        const messagesContainer = chatArea.querySelector('.chat-messages');
                        const newMessage = document.createElement('div');
                        newMessage.className = 'message self';
                        newMessage.dataset.messageId = data.message_id || Date.now();
                        newMessage.innerHTML = `<p>${message}</p><small>Just now</small>`;
                        messagesContainer.appendChild(newMessage);
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    } else {
                        alert(data.message || 'Failed to send message.');
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    alert('Error sending message. Please try again.');
                });
            } else {
                if (!message) {
                    alert('Please enter a message.');
                } else if (!receiverId) {
                    alert('Please select a friend to chat with.');
                }
            }
        }
    });

    function handleFriendAction(url, friendId, buttonContainer, successText) {
        const formData = new FormData();
        formData.append('friend_id', friendId);

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // On success, reload the friends page to show the updated lists
                if(successText === 'Accepted' || successText === 'Rejected'){
                    loadPage('friends');
                } else {
                     const button = buttonContainer.querySelector('button');
                     button.textContent = successText;
                     button.disabled = true;
                }
            } else {
                alert(data.message || 'An error occurred.');
            }
        })
        .catch(console.error);
    }

    // Load default page
    loadPage(defaultPage);
}); 