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

## 🚀 Technical Highlights

- Implemented WebSocket connections for real-time multi-user collaboration
- Built a role-based authorization system with workspace and board-level permissions
- Designed RESTful API endpoints with proper authentication middleware
- Integrated background job processing for scalable notification delivery
- Created a drag-and-drop interface with optimistic UI updates

## 📋 Prerequisites

Before installation, ensure you have:
- PHP 8.2
- Composer
- Node.js
- MySQL
- A Pusher account (free tier works) - [Sign up here](https://pusher.com/)

## ⚙️ Installation

1. **Clone the repository**
```bash
   git clone https://github.com/IR-Abenido/KanbanHub.git
   cd KanbanHub
```

2. **Install dependencies**
```bash
   composer install
   npm install
```

3. **Environment setup**
```bash
   cp .env.example .env
   php artisan key:generate
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

5. **Initialize the database**
```bash
   php artisan migrate
```

6. **Run the application**
```bash
   npm run start
```

   This command runs three processes:
   - `npm run dev` - Vite development server
   - `php artisan serve` - Laravel API server
   - `php artisan queue:work` - Background job processor

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
