/* General Styles */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f0f2f5;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

/* Form Styles */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
}

.form-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

button {
    background-color: #1877f2;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background-color: #166fe5;
}

/* Profile Styles */
.profile-header {
    text-align: center;
    margin-bottom: 30px;
}

.profile-picture {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
}

/* Post Styles */
.post {
    background-color: white;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.post-image {
    max-width: 100%;
    margin-top: 10px;
}

.timestamp {
    color: #65676b;
    font-size: 12px;
}

/* Message Styles */
.message {
    padding: 10px;
    margin: 5px 0;
    border-radius: 15px;
}

.sent {
    background-color: #0084ff;
    color: white;
    margin-left: 20%;
}

.received {
    background-color: #e4e6eb;
    margin-right: 20%;
}

/* Error and Success Messages */
.error {
    color: #dc3545;
    margin: 10px 0;
}

.success {
    color: #28a745;
    margin: 10px 0;
}