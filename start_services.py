#!/usr/bin/env python3
"""
Startup script for HR Management System
Starts both PHP and Python services
"""

import subprocess
import sys
import time
import os
from threading import Thread

def start_python_service():
    """Start the Python Flask service"""
    print("Starting Python service...")
    os.chdir('python')
    subprocess.run([sys.executable, 'app.py'])

def start_php_service():
    """Start the PHP development server"""
    print("Starting PHP service...")
    subprocess.run(['php', '-S', 'localhost:8000', '-t', '.'])

def main():
    print("Starting HR Management System Services...")
    print("=" * 50)
    
    # Start Python service in a separate thread
    python_thread = Thread(target=start_python_service, daemon=True)
    python_thread.start()
    
    # Give Python service time to start
    time.sleep(2)
    
    # Start PHP service (this will block)
    try:
        start_php_service()
    except KeyboardInterrupt:
        print("\nShutting down services...")
        sys.exit(0)

if __name__ == "__main__":
    main()
