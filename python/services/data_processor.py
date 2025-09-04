"""
Data Processing Service
Handles complex data processing, calculations, and report generation
"""

import pandas as pd
import numpy as np
from datetime import datetime, timedelta
import pymysql
import logging
from typing import Dict, List, Any, Optional

logger = logging.getLogger(__name__)

class DataProcessor:
    def __init__(self, db_config: Dict[str, str]):
        self.db_config = db_config
    
    def get_connection(self):
        """Get database connection"""
        try:
            return pymysql.connect(**self.db_config)
        except Exception as e:
            logger.error(f"Database connection error: {e}")
            return None
    
    def calculate_payroll_summary(self, payroll_run_id: int) -> Dict[str, Any]:
        """Calculate comprehensive payroll summary for a specific run"""
        conn = self.get_connection()
        if not conn:
            return {'error': 'Database connection failed'}
        
        try:
            # Get payroll run details
            payroll_query = """
            SELECT * FROM PayrollRuns WHERE PayrollRunID = %s
            """
            payroll_df = pd.read_sql(payroll_query, conn, params=[payroll_run_id])
            
            if payroll_df.empty:
                return {'error': 'Payroll run not found'}
            
            # Get payslip details
            payslips_query = """
            SELECT 
                ps.*,
                e.FirstName,
                e.LastName,
                e.JobTitle,
                d.DepartmentName
            FROM Payslips ps
            JOIN Employees e ON ps.EmployeeID = e.EmployeeID
            LEFT JOIN OrganizationalStructure d ON e.DepartmentID = d.DepartmentID
            WHERE ps.PayrollRunID = %s
            """
            payslips_df = pd.read_sql(payslips_query, conn, params=[payroll_run_id])
            
            # Calculate summary statistics
            summary = {
                'payroll_run_id': payroll_run_id,
                'pay_period_start': payroll_df.iloc[0]['PayPeriodStart'].isoformat(),
                'pay_period_end': payroll_df.iloc[0]['PayPeriodEnd'].isoformat(),
                'total_employees': len(payslips_df),
                'total_gross_pay': payslips_df['GrossPay'].sum(),
                'total_deductions': payslips_df['TotalDeductions'].sum(),
                'total_net_pay': payslips_df['NetPay'].sum(),
                'average_gross_pay': payslips_df['GrossPay'].mean(),
                'average_net_pay': payslips_df['NetPay'].mean(),
                'department_breakdown': self._calculate_department_breakdown(payslips_df),
                'salary_ranges': self._calculate_salary_ranges(payslips_df)
            }
            
            return summary
            
        except Exception as e:
            logger.error(f"Error calculating payroll summary: {e}")
            return {'error': str(e)}
        finally:
            conn.close()
    
    def _calculate_department_breakdown(self, payslips_df: pd.DataFrame) -> List[Dict[str, Any]]:
        """Calculate payroll breakdown by department"""
        if 'DepartmentName' not in payslips_df.columns:
            return []
        
        dept_breakdown = payslips_df.groupby('DepartmentName').agg({
            'GrossPay': ['sum', 'mean', 'count'],
            'NetPay': ['sum', 'mean']
        }).round(2)
        
        dept_breakdown.columns = ['total_gross', 'avg_gross', 'employee_count', 'total_net', 'avg_net']
        dept_breakdown = dept_breakdown.reset_index()
        
        return dept_breakdown.to_dict('records')
    
    def _calculate_salary_ranges(self, payslips_df: pd.DataFrame) -> Dict[str, int]:
        """Calculate salary distribution ranges"""
        gross_pay = payslips_df['GrossPay']
        
        ranges = {
            'under_30000': len(gross_pay[gross_pay < 30000]),
            '30000_50000': len(gross_pay[(gross_pay >= 30000) & (gross_pay < 50000)]),
            '50000_75000': len(gross_pay[(gross_pay >= 50000) & (gross_pay < 75000)]),
            '75000_100000': len(gross_pay[(gross_pay >= 75000) & (gross_pay < 100000)]),
            'over_100000': len(gross_pay[gross_pay >= 100000])
        }
        
        return ranges
    
    def generate_employee_analytics(self, start_date: str, end_date: str) -> Dict[str, Any]:
        """Generate comprehensive employee analytics for date range"""
        conn = self.get_connection()
        if not conn:
            return {'error': 'Database connection failed'}
        
        try:
            # Employee demographics
            demographics_query = """
            SELECT 
                Gender,
                MaritalStatus,
                COUNT(*) as count,
                AVG(DATEDIFF(CURDATE(), HireDate)) as avg_tenure_days
            FROM Employees
            WHERE IsActive = 1
            GROUP BY Gender, MaritalStatus
            """
            demographics_df = pd.read_sql(demographics_query, conn)
            
            # Department distribution
            dept_query = """
            SELECT 
                d.DepartmentName,
                COUNT(e.EmployeeID) as employee_count,
                AVG(s.BaseSalary) as avg_salary
            FROM OrganizationalStructure d
            LEFT JOIN Employees e ON d.DepartmentID = e.DepartmentID AND e.IsActive = 1
            LEFT JOIN Salaries s ON e.EmployeeID = s.EmployeeID AND s.IsActive = 1
            GROUP BY d.DepartmentID, d.DepartmentName
            ORDER BY employee_count DESC
            """
            dept_df = pd.read_sql(dept_query, conn)
            
            # Turnover analysis
            turnover_query = """
            SELECT 
                YEAR(TerminationDate) as year,
                MONTH(TerminationDate) as month,
                COUNT(*) as terminations
            FROM Employees
            WHERE TerminationDate BETWEEN %s AND %s
            GROUP BY YEAR(TerminationDate), MONTH(TerminationDate)
            ORDER BY year, month
            """
            turnover_df = pd.read_sql(turnover_query, conn, params=[start_date, end_date])
            
            analytics = {
                'demographics': demographics_df.to_dict('records'),
                'department_distribution': dept_df.to_dict('records'),
                'turnover_analysis': turnover_df.to_dict('records'),
                'summary': {
                    'total_active_employees': len(dept_df[dept_df['employee_count'] > 0]),
                    'total_departments': len(dept_df),
                    'avg_tenure_days': demographics_df['avg_tenure_days'].mean()
                }
            }
            
            return analytics
            
        except Exception as e:
            logger.error(f"Error generating employee analytics: {e}")
            return {'error': str(e)}
        finally:
            conn.close()
    
    def calculate_attendance_metrics(self, employee_id: int, start_date: str, end_date: str) -> Dict[str, Any]:
        """Calculate attendance metrics for specific employee"""
        conn = self.get_connection()
        if not conn:
            return {'error': 'Database connection failed'}
        
        try:
            # This would typically query attendance/timesheet data
            # For now, return placeholder metrics
            metrics = {
                'employee_id': employee_id,
                'period': f"{start_date} to {end_date}",
                'total_days': 30,
                'present_days': 28,
                'absent_days': 2,
                'late_days': 3,
                'attendance_rate': 93.33,
                'punctuality_rate': 89.29
            }
            
            return metrics
            
        except Exception as e:
            logger.error(f"Error calculating attendance metrics: {e}")
            return {'error': str(e)}
        finally:
            conn.close()
    
    def generate_financial_summary(self, year: int) -> Dict[str, Any]:
        """Generate annual financial summary"""
        conn = self.get_connection()
        if not conn:
            return {'error': 'Database connection failed'}
        
        try:
            # Payroll costs by month
            payroll_query = """
            SELECT 
                MONTH(PayPeriodEnd) as month,
                SUM(TotalGrossPay) as total_gross,
                SUM(TotalDeductions) as total_deductions,
                SUM(TotalNetPay) as total_net
            FROM PayrollRuns
            WHERE YEAR(PayPeriodEnd) = %s AND Status = 'Completed'
            GROUP BY MONTH(PayPeriodEnd)
            ORDER BY month
            """
            payroll_df = pd.read_sql(payroll_query, conn, params=[year])
            
            # Benefits and deductions breakdown
            benefits_query = """
            SELECT 
                dt.DeductionTypeName,
                SUM(d.Amount) as total_amount
            FROM Deductions d
            JOIN DeductionTypes dt ON d.DeductionTypeID = dt.DeductionTypeID
            WHERE YEAR(d.EffectiveDate) = %s
            GROUP BY dt.DeductionTypeName
            ORDER BY total_amount DESC
            """
            benefits_df = pd.read_sql(benefits_query, conn, params=[year])
            
            summary = {
                'year': year,
                'monthly_payroll': payroll_df.to_dict('records'),
                'benefits_breakdown': benefits_df.to_dict('records'),
                'annual_totals': {
                    'total_gross_pay': payroll_df['total_gross'].sum(),
                    'total_deductions': payroll_df['total_deductions'].sum(),
                    'total_net_pay': payroll_df['total_net'].sum()
                }
            }
            
            return summary
            
        except Exception as e:
            logger.error(f"Error generating financial summary: {e}")
            return {'error': str(e)}
        finally:
            conn.close()
