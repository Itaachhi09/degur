# HR Management System - API Documentation

## Overview
This HR Management System uses a modern REST API architecture with PHP for core functionality and Python for advanced analytics and data processing.

## Technology Stack
- **Backend**: PHP 8+ with PDO for database operations
- **Frontend**: JavaScript (ES6+) with Tailwind CSS
- **Analytics**: Python 3.8+ with Flask, Pandas, and NumPy
- **Database**: MySQL 8.0+
- **Authentication**: JWT (JSON Web Tokens)

## API Base URLs
- **PHP API**: `http://localhost/php/api/`
- **Python API**: `http://localhost:5000/api/`

## Authentication
All API endpoints (except login) require a JWT token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## PHP API Endpoints

### Authentication

#### POST /auth/login
Authenticate user and receive JWT token.

**Request Body:**
```json
{
    "username": "string",
    "password": "string"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "token": "jwt_token_here",
        "user": {
            "user_id": 1,
            "employee_id": 1,
            "username": "john.doe",
            "full_name": "John Doe",
            "role": "System Admin"
        },
        "expires_in": 86400
    }
}
```

#### POST /auth/logout
Logout user (client-side token removal).

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### Employees

#### GET /employees
Get list of employees with pagination and filtering.

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `limit` (int): Items per page (default: 20, max: 100)
- `search` (string): Search by name or email
- `department` (int): Filter by department ID
- `status` (string): Filter by status (active/inactive)

**Response:**
```json
{
    "success": true,
    "data": {
        "employees": [
            {
                "EmployeeID": 1,
                "FirstName": "John",
                "LastName": "Doe",
                "Email": "john.doe@company.com",
                "JobTitle": "Software Developer",
                "DepartmentName": "IT",
                "IsActive": 1,
                "HireDate": "2023-01-15",
                "full_name": "John Doe",
                "status": "Active"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 50,
            "total_pages": 3,
            "has_next_page": true,
            "has_prev_page": false
        }
    }
}
```

#### POST /employees
Create a new employee.

**Request Body:**
```json
{
    "FirstName": "Jane",
    "LastName": "Smith",
    "Email": "jane.smith@company.com",
    "PhoneNumber": "+1234567890",
    "JobTitle": "HR Manager",
    "DepartmentID": 2,
    "HireDate": "2024-01-01"
}
```

#### GET /employees/{id}
Get specific employee details.

#### PUT /employees/{id}
Update employee information.

#### DELETE /employees/{id}
Delete employee (soft delete).

### Payroll

#### GET /payroll/runs
Get payroll runs with pagination.

#### POST /payroll/runs
Create new payroll run.

#### GET /payroll/salaries
Get salary information.

#### GET /payroll/payslips
Get payslips with filtering.

#### GET /payroll/payslips/{id}
Get specific payslip details.

### Claims

#### GET /claims
Get employee claims.

#### POST /claims
Submit new claim.

#### PUT /claims/{id}
Update claim status.

#### DELETE /claims/{id}
Delete claim.

### Departments

#### GET /departments
Get organizational structure.

#### POST /departments
Create new department.

#### PUT /departments/{id}
Update department.

#### DELETE /departments/{id}
Delete department.

## Python API Endpoints

### Analytics

#### GET /analytics/dashboard
Get comprehensive dashboard analytics.

**Response:**
```json
{
    "success": true,
    "data": {
        "employee_stats": {
            "total_employees": 50,
            "active_employees": 48,
            "inactive_employees": 2,
            "avg_tenure_days": 365
        },
        "payroll_stats": {
            "total_payroll_runs": 12,
            "total_gross_pay": 500000,
            "avg_gross_pay": 41666.67,
            "last_payroll_date": "2024-01-31"
        },
        "department_stats": [
            {
                "DepartmentName": "IT",
                "employee_count": 15
            }
        ],
        "recent_activities": [
            {
                "type": "employee_added",
                "description": "New employee added",
                "timestamp": "2024-01-31T10:00:00Z"
            }
        ]
    }
}
```

#### GET /analytics/reports
Get various analytics reports.

**Query Parameters:**
- `type` (string): Report type (payroll, employee, attendance, all)

#### GET /analytics/metrics
Get key performance metrics.

## Error Responses

All endpoints return consistent error responses:

```json
{
    "success": false,
    "error": {
        "message": "Error description",
        "code": 400,
        "timestamp": "2024-01-31T10:00:00Z",
        "details": "Additional error details (optional)"
    }
}
```

## HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `422` - Validation Error
- `500` - Internal Server Error

## Rate Limiting

- **PHP API**: 1000 requests per hour per IP
- **Python API**: 500 requests per hour per IP

## CORS Configuration

The API supports CORS for cross-origin requests:
- Allowed Origins: `*` (configure for production)
- Allowed Methods: GET, POST, PUT, DELETE, OPTIONS
- Allowed Headers: Content-Type, Authorization, X-Requested-With

## Getting Started

1. **Install Dependencies:**
   ```bash
   # PHP dependencies
   composer install
   
   # Python dependencies
   cd python
   pip install -r requirements.txt
   ```

2. **Configure Environment:**
   - Copy `.env.example` to `.env`
   - Update database credentials
   - Set JWT secret key

3. **Start Services:**
   ```bash
   # Start both PHP and Python services
   python start_services.py
   
   # Or start individually
   php -S localhost:8000 -t .
   cd python && python app.py
   ```

4. **Access the Application:**
   - Frontend: `http://localhost:8000`
   - PHP API: `http://localhost:8000/php/api/`
   - Python API: `http://localhost:5000/api/`

## Security Considerations

1. **JWT Tokens**: Store securely, implement refresh mechanism
2. **Input Validation**: All inputs are validated and sanitized
3. **SQL Injection**: Using PDO prepared statements
4. **CORS**: Configure appropriately for production
5. **Rate Limiting**: Implement based on your needs
6. **HTTPS**: Use in production environment

## Support

For API support and questions, please contact the development team.
