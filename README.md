# KanbanHub

A full-stack Kanban board application with real-time collaboration features, built to demonstrate modern web development practices and full-stack capabilities.

## 🎯 Project Purpose

Built as a portfolio project to showcase:
- Full-stack development with Laravel and React
- Real-time collaboration using WebSockets
- Complex state management with Redux
- RESTful API design and authentication
- Role-based access control systems

## ✨ Key Features

- **Workspace & Board Management** - Create, update, archive, and delete workspaces and collaborative boards
- **Real-time Collaboration** - Live updates across all connected users via Pusher WebSockets
- **Task Management** - Drag-and-drop tasks between lists and set up due dates and upload files
- **Team Communication** - Member commenting system and activity logs in tasks
- **Access Control** - Role-based permissions for workspaces and boards
- **Notification System** - In-app notification system for workspace invitations and important events
- **Background Processing** - Laravel Queues for asynchronous job handling mainly for web socket events

## 🛠️ Tech Stack

**Frontend:**
- React 19
- Redux (State Management)
- Tailwind CSS
- Vite

**Backend:**
- Laravel 11
- MySQL
- Pusher (WebSocket Communication)
- Laravel Queues

## 📋 Prerequisites

Before installation, ensure you have:
- Docker Desktop (https://www.docker.com/products/docker-desktop/)
- [WSL2](https://learn.microsoft.com/en-us/windows/wsl/install) (Windows users)
- A Pusher account (free tier works) - [Sign up here](https://pusher.com/)
- All commands should be run in your WSL2 terminal

## ⚙️ Installation

1. **Clone the repository (inside your WSL2 home directory)**
```bash
    git clone https://github.com/IR-Abenido/KanbanHub.git in the wsl directory
    cd KanbanHub
```

2. **Environment setup**
```bash
    cp .env.example .env
```

3. **Install PHP dependencies (before Sail is available)**
```bash
    docker run --rm -v $(pwd):/app -e COMPOSER_ALLOW_SUPERUSER=1 composer install --ignore-platform-reqs
```

4. **Configure your `.env` file**
    Update these essential variables:
```env
    # Database
    DB_DATABASE=kanbanhub
    DB_USERNAME=your_username
    DB_PASSWORD=your_password

    # Pusher (get these from your Pusher dashboard)
    PUSHER_APP_ID=your_app_id
    PUSHER_APP_KEY=your_app_key
    PUSHER_APP_SECRET=your_app_secret
    PUSHER_APP_CLUSTER=your_cluster

    # Queue Connection
    QUEUE_CONNECTION=database
```

5. **Start the Docker containers**
```bash
    ./vendor/bin/sail up -d
```

6. **Generate app key and install JS dependencies**
```bash
    ./vendor/bin/sail artisan key:generate
    ./vendor/bin/sail npm install
```

7. **Run database migrations**
```bash
    ./vendor/bin/sail artisan migrate
```
7. **Start the development servers**
```bash
    ./vendor/bin/sail npm run start
```

## 📧 Email Configuration (Optional)

While the app works with in-app notifications for workspace invitations, you can enable workspace invitations via email by configuring a mail service in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
```

## 📝 Notes

- This is a portfolio/learning project built for demonstrating full-stack development skills
- The invitation system currently works through in-app notifications; email functionality requires SMTP configuration
- Background jobs use database queue driver by default (can be configured to use Redis)

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 👤 Author

**Ian Rafael T. Abenido**
- GitHub: [@IR-Abenido](https://github.com/IR-Abenido)
---
