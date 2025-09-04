# HR Management System

A modern, full-stack HR management system built with PHP, Python, and JavaScript, featuring REST API architecture, advanced analytics, and responsive design.

## 🚀 Technology Stack

- **Backend**: PHP 8+ with PDO for database operations
- **Frontend**: JavaScript (ES6+) with Tailwind CSS
- **Analytics**: Python 3.9+ with Flask, Pandas, and NumPy
- **Database**: MySQL 8.0+
- **Authentication**: JWT (JSON Web Tokens)
- **Deployment**: Docker & Docker Compose

## ✨ Features

### Core HR Management
- Employee management and profiles
- Organizational structure
- Document management
- User role-based access control

### Payroll System
- Salary management
- Payroll runs and processing
- Payslip generation
- Bonuses and deductions

### Analytics & Reporting
- Real-time dashboard with charts
- Advanced analytics via Python microservices
- Custom reports generation
- Key performance metrics

### Modern Architecture
- RESTful API design
- JWT-based authentication
- Microservices architecture
- Responsive web interface
- CORS-enabled for cross-origin requests

## 🛠️ Installation

### Prerequisites
- PHP 8.0 or higher
- Python 3.9 or higher
- MySQL 8.0 or higher
- Composer
- Node.js (for development)

### Option 1: Docker (Recommended)

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd hr4
   ```

2. **Start with Docker Compose**
   ```bash
   docker-compose up -d
   ```

3. **Access the application**
   - Frontend: http://localhost:8000
   - Python API: http://localhost:5001

### Option 2: Manual Installation

1. **Clone and setup PHP**
   ```bash
   git clone <repository-url>
   cd hr4
   composer install
   ```

2. **Setup Python environment**
   ```bash
   cd python
   pip install -r requirements.txt
   ```

3. **Configure database**
   - Import `hr4_complete_database.sql` to your MySQL database
   - Update database credentials in `.env` files

4. **Start services**
   ```bash
   # Start PHP server
   php -S localhost:8000 -t .
   
   # Start Python service (in another terminal)
   cd python
   python app.py
   ```

## 📚 API Documentation

### PHP API Endpoints

#### Authentication
- `POST /php/api/auth/login` - User login
- `POST /php/api/auth/logout` - User logout

#### Employees
- `GET /php/api/employees` - List employees
- `POST /php/api/employees` - Create employee
- `GET /php/api/employees/{id}` - Get employee details
- `PUT /php/api/employees/{id}` - Update employee
- `DELETE /php/api/employees/{id}` - Delete employee

#### Payroll
- `GET /php/api/payroll/runs` - Get payroll runs
- `POST /php/api/payroll/runs` - Create payroll run
- `GET /php/api/payroll/salaries` - Get salaries
- `GET /php/api/payroll/payslips` - Get payslips

### Python API Endpoints

#### Analytics
- `GET /api/analytics/dashboard` - Dashboard analytics
- `GET /api/analytics/reports` - Generate reports
- `GET /api/analytics/metrics` - Key metrics

For complete API documentation, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md).

## 🔧 Configuration

### Environment Variables

Create `.env` files in the root and `python/` directories:

```env
# Database
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=hr441

# JWT
JWT_SECRET_KEY=your_secret_key

# Application
APP_ENV=development
```

### Database Setup

1. Create MySQL database:
   ```sql
   CREATE DATABASE hr441 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Import schema:
   ```bash
   mysql -u root -p hr441 < hr4_complete_database.sql
   ```

## 🚀 Usage

### Default Login
- **Username**: admin
- **Password**: admin123
- **Role**: System Admin

### User Roles
- **System Admin**: Full system access
- **HR Admin**: HR management functions
- **Manager**: Team management
- **Employee**: Self-service portal

## 📊 Features Overview

### Dashboard
- Role-based dashboard views
- Real-time analytics and charts
- Quick action buttons
- Key performance indicators

### Employee Management
- Complete employee profiles
- Document management
- Organizational hierarchy
- Search and filtering

### Payroll System
- Automated payroll processing
- Salary calculations
- Payslip generation
- Benefits management

### Analytics
- Department distribution
- Employee demographics
- Payroll analytics
- Custom report generation

## 🔒 Security Features

- JWT-based authentication
- Role-based access control
- Input validation and sanitization
- SQL injection prevention
- CORS configuration
- Secure password hashing

## 🐳 Docker Deployment

### Production Deployment

1. **Update environment variables**
   ```bash
   cp .env.example .env
   # Edit .env with production values
   ```

2. **Build and deploy**
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

### Development

```bash
# Start development environment
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

## 🧪 Testing

### API Testing
```bash
# Test PHP API
curl -X GET http://localhost:8000/php/api/employees

# Test Python API
curl -X GET http://localhost:5000/api/analytics/dashboard
```

### Frontend Testing
- Open http://localhost:8000 in your browser
- Test all user roles and permissions
- Verify responsive design

## 📈 Performance

### Optimization Features
- Database query optimization
- Caching mechanisms
- Lazy loading for large datasets
- Efficient pagination
- Chart rendering optimization

### Monitoring
- Error logging
- Performance metrics
- Database query analysis
- API response times

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the API documentation
- Review the troubleshooting guide

## 🔄 Updates

### Recent Changes
- ✅ RESTful API implementation
- ✅ Python analytics integration
- ✅ JWT authentication
- ✅ Docker containerization
- ✅ Enhanced dashboard with charts
- ✅ Improved error handling

### Roadmap
- [ ] Mobile application
- [ ] Advanced reporting features
- [ ] Integration with external systems
- [ ] Real-time notifications
- [ ] Advanced security features

---

**Built with ❤️ for modern HR management**
