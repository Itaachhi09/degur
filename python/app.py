"""
HR Management System - Python Microservices
Main Flask application for data processing, analytics, and reporting
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from flask_jwt_extended import JWTManager, jwt_required, get_jwt_identity
import pymysql
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
import os
from dotenv import load_dotenv
import logging
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

# Load environment variables
load_dotenv()

# Initialize Flask app
app = Flask(__name__)
app.config['JWT_SECRET_KEY'] = os.getenv('JWT_SECRET_KEY', 'default_secret')
app.config['JWT_ACCESS_TOKEN_EXPIRES'] = timedelta(hours=24)

# Initialize extensions
jwt = JWTManager(app)
CORS(app)

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Database configuration
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASS', ''),
    'database': os.getenv('DB_NAME', 'hr441'),
    'charset': 'utf8mb4'
}

def get_db_connection():
    """Get database connection"""
    try:
        connection = pymysql.connect(**DB_CONFIG)
        return connection
    except Exception as e:
        logger.error(f"Database connection error: {e}")
        return None

def send_email_smtp(recipient_email: str, subject: str, body: str, is_html: bool = False) -> tuple[bool, str]:
    """Send an email using SMTP settings from environment variables.

    Returns (success, message)
    """
    smtp_host = os.getenv('SMTP_HOST', 'smtp.gmail.com')
    smtp_port = int(os.getenv('SMTP_PORT', '587'))
    smtp_user = os.getenv('SMTP_USER', '')
    smtp_pass = os.getenv('SMTP_PASS', '')
    smtp_from = os.getenv('SMTP_FROM', smtp_user)
    use_tls = os.getenv('SMTP_USE_TLS', 'true').lower() == 'true'

    if not smtp_user or not smtp_pass or not smtp_from:
        return False, 'SMTP credentials not configured'

    try:
        message = MIMEMultipart('alternative') if is_html else MIMEMultipart()
        message['From'] = smtp_from
        message['To'] = recipient_email
        message['Subject'] = subject

        if is_html:
            mime_part = MIMEText(body, 'html')
        else:
            mime_part = MIMEText(body, 'plain')
        message.attach(mime_part)

        server = smtplib.SMTP(smtp_host, smtp_port, timeout=15)
        try:
            if use_tls:
                server.starttls()
            server.login(smtp_user, smtp_pass)
            server.sendmail(smtp_from, [recipient_email], message.as_string())
        finally:
            server.quit()

        return True, 'Email sent'
    except Exception as e:
        logger.error(f"SMTP send error: {e}")
        return False, f"SMTP send error: {e}"

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'timestamp': datetime.now().isoformat(),
        'service': 'hr-python-api'
    })

@app.route('/api/notify/email', methods=['POST'])
def notify_email():
    """Send an email notification via SMTP.

    Request JSON:
    {
        "to": "recipient@example.com",
        "subject": "Subject line",
        "body": "Text or HTML",
        "is_html": false
    }
    """
    try:
        payload = request.get_json(silent=True) or {}
        recipient = (payload.get('to') or '').strip()
        subject = (payload.get('subject') or '').strip()
        body = payload.get('body') or ''
        is_html = bool(payload.get('is_html', False))

        if not recipient or not subject or not body:
            return jsonify({'success': False, 'error': 'Missing to/subject/body'}), 400

        ok, msg = send_email_smtp(recipient, subject, body, is_html)
        status = 200 if ok else 500
        return jsonify({'success': ok, 'message': msg}), status
    except Exception as e:
        logger.error(f"notify_email error: {e}")
        return jsonify({'success': False, 'error': 'Failed to send email'}), 500

@app.route('/api/analytics/dashboard', methods=['GET'])
@jwt_required()
def get_analytics_dashboard():
    """Get comprehensive analytics dashboard data"""
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Database connection failed'}), 500

        # Get employee statistics
        employee_stats = get_employee_statistics(conn)
        
        # Get payroll statistics
        payroll_stats = get_payroll_statistics(conn)
        
        # Get department distribution
        department_stats = get_department_statistics(conn)
        
        # Get recent activities
        recent_activities = get_recent_activities(conn)
        
        conn.close()
        
        return jsonify({
            'success': True,
            'data': {
                'employee_stats': employee_stats,
                'payroll_stats': payroll_stats,
                'department_stats': department_stats,
                'recent_activities': recent_activities
            }
        })
        
    except Exception as e:
        logger.error(f"Analytics dashboard error: {e}")
        return jsonify({'error': 'Failed to generate analytics'}), 500

@app.route('/api/analytics/reports', methods=['GET'])
@jwt_required()
def get_analytics_reports():
    """Get available analytics reports"""
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Database connection failed'}), 500

        report_type = request.args.get('type', 'all')
        
        if report_type == 'payroll' or report_type == 'all':
            payroll_report = generate_payroll_report(conn)
        else:
            payroll_report = None
            
        if report_type == 'employee' or report_type == 'all':
            employee_report = generate_employee_report(conn)
        else:
            employee_report = None
            
        if report_type == 'attendance' or report_type == 'all':
            attendance_report = generate_attendance_report(conn)
        else:
            attendance_report = None
        
        conn.close()
        
        return jsonify({
            'success': True,
            'data': {
                'payroll_report': payroll_report,
                'employee_report': employee_report,
                'attendance_report': attendance_report
            }
        })
        
    except Exception as e:
        logger.error(f"Analytics reports error: {e}")
        return jsonify({'error': 'Failed to generate reports'}), 500

@app.route('/api/analytics/metrics', methods=['GET'])
@jwt_required()
def get_analytics_metrics():
    """Get key performance metrics"""
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({'error': 'Database connection failed'}), 500

        metrics = {
            'employee_metrics': calculate_employee_metrics(conn),
            'payroll_metrics': calculate_payroll_metrics(conn),
            'productivity_metrics': calculate_productivity_metrics(conn)
        }
        
        conn.close()
        
        return jsonify({
            'success': True,
            'data': metrics
        })
        
    except Exception as e:
        logger.error(f"Analytics metrics error: {e}")
        return jsonify({'error': 'Failed to calculate metrics'}), 500

def get_employee_statistics(conn):
    """Get employee statistics"""
    query = """
    SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) as active_employees,
        SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) as inactive_employees,
        AVG(CASE WHEN IsActive = 1 THEN DATEDIFF(CURDATE(), HireDate) ELSE NULL END) as avg_tenure_days
    FROM Employees
    """
    
    df = pd.read_sql(query, conn)
    return df.to_dict('records')[0]

def get_payroll_statistics(conn):
    """Get payroll statistics"""
    query = """
    SELECT 
        COUNT(*) as total_payroll_runs,
        SUM(TotalGrossPay) as total_gross_pay,
        AVG(TotalGrossPay) as avg_gross_pay,
        MAX(PayPeriodEnd) as last_payroll_date
    FROM PayrollRuns
    WHERE Status = 'Completed'
    """
    
    df = pd.read_sql(query, conn)
    return df.to_dict('records')[0]

def get_department_statistics(conn):
    """Get department distribution"""
    query = """
    SELECT 
        d.DepartmentName,
        COUNT(e.EmployeeID) as employee_count
    FROM OrganizationalStructure d
    LEFT JOIN Employees e ON d.DepartmentID = e.DepartmentID AND e.IsActive = 1
    GROUP BY d.DepartmentID, d.DepartmentName
    ORDER BY employee_count DESC
    """
    
    df = pd.read_sql(query, conn)
    return df.to_dict('records')

def get_recent_activities(conn):
    """Get recent system activities"""
    # This would typically come from an activities/audit log table
    # For now, return a placeholder
    return [
        {'type': 'employee_added', 'description': 'New employee added', 'timestamp': datetime.now().isoformat()},
        {'type': 'payroll_processed', 'description': 'Monthly payroll processed', 'timestamp': datetime.now().isoformat()}
    ]

def generate_payroll_report(conn):
    """Generate comprehensive payroll report"""
    query = """
    SELECT 
        pr.PayrollRunID,
        pr.PayPeriodStart,
        pr.PayPeriodEnd,
        pr.TotalGrossPay,
        pr.TotalDeductions,
        pr.TotalNetPay,
        pr.Status,
        COUNT(ps.PayslipID) as payslip_count
    FROM PayrollRuns pr
    LEFT JOIN Payslips ps ON pr.PayrollRunID = ps.PayrollRunID
    GROUP BY pr.PayrollRunID
    ORDER BY pr.PayPeriodEnd DESC
    LIMIT 12
    """
    
    df = pd.read_sql(query, conn)
    return df.to_dict('records')

def generate_employee_report(conn):
    """Generate employee demographics report"""
    query = """
    SELECT 
        Gender,
        COUNT(*) as count,
        AVG(DATEDIFF(CURDATE(), HireDate)) as avg_tenure_days
    FROM Employees
    WHERE IsActive = 1
    GROUP BY Gender
    """
    
    df = pd.read_sql(query, conn)
    return df.to_dict('records')

def generate_attendance_report(conn):
    """Generate attendance report"""
    # This would query attendance/timesheet data
    # For now, return placeholder data
    return [
        {'date': '2024-01-01', 'present': 45, 'absent': 5, 'late': 3},
        {'date': '2024-01-02', 'present': 47, 'absent': 3, 'late': 2}
    ]

def calculate_employee_metrics(conn):
    """Calculate employee performance metrics"""
    query = """
    SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) as active_employees,
        AVG(CASE WHEN IsActive = 1 THEN DATEDIFF(CURDATE(), HireDate) ELSE NULL END) as avg_tenure_days
    FROM Employees
    """
    
    df = pd.read_sql(query, conn)
    return df.to_dict('records')[0]

def calculate_payroll_metrics(conn):
    """Calculate payroll performance metrics"""
    query = """
    SELECT 
        COUNT(*) as total_runs,
        AVG(TotalGrossPay) as avg_gross_pay,
        SUM(TotalGrossPay) as total_paid
    FROM PayrollRuns
    WHERE Status = 'Completed'
    AND PayPeriodEnd >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    """
    
    df = pd.read_sql(query, conn)
    return df.to_dict('records')[0]

def calculate_productivity_metrics(conn):
    """Calculate productivity metrics"""
    # This would typically calculate based on attendance, performance data, etc.
    # For now, return placeholder metrics
    return {
        'attendance_rate': 95.5,
        'productivity_score': 87.2,
        'overtime_hours': 120.5
    }

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)
