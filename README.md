# Exam Management System 

A modern, secure web application for managing online examinations, featuring an elegant dark theme UI for the login system and separate dashboards for teachers and students.

## Features

### Authentication System
- Secure login system with modern UI and dark theme
- Role-based access control (teachers/students)
- Password encryption using PHP's password hashing
- Interactive login form with animated elements
- Session management for secure access

### Teacher Dashboard
- Modern sidebar navigation with icon support
- Real-time statistics display with animated counters:
  - Total number of students
  - Number of teachers
  - Total exams created
- Quick access to main functions:
  - Create new exams
  - Add students
  - Grade exams
- Responsive design for all screen sizes

### Student Dashboard
- Clean, intuitive interface
- Easy access to available exams
- Personal progress tracking
- Exam schedule overview
- Responsive navigation menu

### Exam Management
- Create and manage exams with:
  - Custom duration settings
  - Start and end dates
  - Multiple attempt options
  - Points allocation
- Different question types:
  - Multiple Choice Questions (MCQ)
  - Open-ended questions
- Grade submission system

## Technical Stack

### Backend
- PHP 7.4+
- MySQL 5.7+
- Session management
- PDO/MySQLi database connections

### Frontend
- HTML5
- CSS3 with custom properties (variables)
- Modern JavaScript (ES6+)
- Font Awesome icons
- Responsive design principles

### Security
- Password hashing
- SQL injection prevention
- Session security
- Input validation
- CSRF protection

## UI Features

### Theme Colors
```css
:root {
    --primary-color: #2563eb;
    --secondary-color: #1e40af;
    --accent-color: #3b82f6;
    --danger-color: #dc2626;
    --warning-color: #f59e0b;
    --success-color: #10b981;
    --background-light: #f8fafc;
    --text-dark: #1e293b;
    --text-light: #f8fafc;
}
```

### Login Page
- Dark theme with gradient backgrounds
- Animated input fields
- Interactive hover effects
- Responsive layout
- Password strength indicator

### Teacher Dashboard
- Sidebar navigation with icons
- Statistics cards with animations
- Gradient accents
- Shadow effects
- Responsive grid layout

### Student Dashboard
- Clean card-based layout
- Easy navigation menu
- Status indicators
- Progress tracking display

## File Structure
```
├── index.php
├── login.php
├── style.css
├── teacher_dashboard.php
├── student_dashboard.php
├── add_students.php
├── create_exam.php
├── grade_exams.php
├── hash_passwords.php
├── exam_system.sql
└── assets/
    ├── css/
    └── js/
```

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/exam-management-system.git
```

2. Import the database:
```bash
mysql -u root -p < exam_system.sql
```

3. Configure database connection:
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "exam_system";
```

4. Deploy to web server:
```bash
mv exam-management-system /var/www/html/
```

5. Set permissions:
```bash
chmod -R 755 /var/www/html/exam-management-system
```

## Usage

### Teacher Access
1. Login with teacher credentials
2. Access dashboard features:
   - View statistics
   - Create exams
   - Manage students
   - Grade submissions

### Student Access
1. Login with student credentials
2. Navigate dashboard to:
   - View available exams
   - Take exams
   - Check grades
   - Track progress

## Development Roadmap

### Immediate Goals
- [ ] Add exam templates feature
- [ ] Implement real-time exam monitoring
- [ ] Add batch student import
- [ ] Enhance statistics dashboard

### Future Enhancements
- [ ] Dark/Light theme toggle
- [ ] Advanced analytics
- [ ] PDF report generation
- [ ] Email notifications
- [ ] Mobile app integration

## Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature-name`
3. Commit changes: `git commit -am 'Add feature'`
4. Push to branch: `git push origin feature-name`
5. Submit Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please check the documentation or create an issue in the GitHub repository.
