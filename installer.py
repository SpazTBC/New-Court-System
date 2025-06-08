#!/usr/bin/env python3
"""
Court System Installer
Detects web servers and installs the Court System application
"""

import os
import sys
import shutil
import subprocess
import psutil
import socket
import platform
from pathlib import Path
import time

class CourtSystemInstaller:
    def __init__(self):
        self.detected_servers = []
        self.web_root = None  # Initialize web_root attribute
        self.mysql_paths = {
            'xampp': {
                'windows': 'C:/xampp/mysql/bin/mysql.exe',
                'linux': '/opt/lampp/bin/mysql',
                'mac': '/Applications/XAMPP/xamppfiles/bin/mysql'
            },
            'wamp': {
                'windows': 'C:/wamp64/bin/mysql/mysql*/bin/mysql.exe'
            },
            'mamp': {
                'mac': '/Applications/MAMP/Library/bin/mysql'
            },
            'system': {
                'windows': 'mysql.exe',
                'linux': '/usr/bin/mysql',
                'mac': '/usr/local/bin/mysql'
            }
        }
        self.web_roots = {
            'xampp': {
                'windows': 'C:/xampp/htdocs',
                'linux': '/opt/lampp/htdocs',
                'mac': '/Applications/XAMPP/xamppfiles/htdocs'
            },
            'wamp': {
                'windows': 'C:/wamp64/www'
            },
            'mamp': {
                'mac': '/Applications/MAMP/htdocs'
            },
            'nginx': {
                'windows': 'C:/nginx/html',
                'linux': '/var/www/html',
                'mac': '/usr/local/var/www'
            },
            'apache': {
                'linux': '/var/www/html',
                'mac': '/usr/local/var/www'
            }
        }
        
    def print_banner(self):
        """Print installer banner"""
        print("=" * 60)
        print("           COURT SYSTEM INSTALLER")
        print("=" * 60)
        print()
        
    def detect_web_servers(self):
        """Detect running web servers"""
        print("[INFO] Detecting web servers...")
        
        # Process names to look for - ORDER MATTERS (most specific first)
        server_processes = {
            # Server management tools (highest priority)
            'xampp-control.exe': ('XAMPP Control Panel', 'xampp'),
            'wampmanager.exe': ('WAMP Server', 'wamp'),
            'mamp': ('MAMP Server', 'mamp'),
            
            # Specific web servers (medium priority)
            'nginx.exe': ('Nginx Web Server', 'nginx'),
            'nginx': ('Nginx Web Server', 'nginx'),
            'w3wp.exe': ('IIS Web Server', 'iis'),
            
            # Generic processes (lowest priority)
            'httpd.exe': ('Apache HTTP Server', 'apache'),
            'httpd': ('Apache HTTP Server', 'apache'),
            'mysqld.exe': ('MySQL Server', 'mysql'),
            'mysqld': ('MySQL Server', 'mysql')
        }
        
        detected = []
        detected_types = set()
        
        # Check running processes
        for proc in psutil.process_iter(['pid', 'name', 'exe']):
            try:
                proc_name = proc.info['name'].lower()
                for server_proc, (server_name, server_type) in server_processes.items():
                    if server_proc.lower() in proc_name:
                        detected.append({
                            'name': server_name,
                            'type': server_type,
                            'process': proc.info['name'],
                            'pid': proc.info['pid'],
                            'exe': proc.info['exe'],
                            'priority': self.get_process_priority(server_type)
                        })
                        detected_types.add(server_type)
            except (psutil.NoSuchProcess, psutil.AccessDenied):
                continue
        
        # Sort by priority (higher priority first)
        detected.sort(key=lambda x: x['priority'], reverse=True)
        
        # Check for common web server ports
        web_ports = [80, 443, 8080, 3000, 8000]
        active_ports = []
        
        for port in web_ports:
            if self.is_port_open('localhost', port):
                active_ports.append(port)
                
        print(f"[DETECTED] Active web server processes: {len(detected)}")
        for server in detected:
            print(f"  - {server['name']} (PID: {server['pid']})")
            
        print(f"[DETECTED] Active web ports: {active_ports}")
        
        # Determine the primary server type
        if detected:
            primary_server = detected[0]  # Highest priority
            print(f"[PRIMARY] Detected primary server: {primary_server['name']} ({primary_server['type']})")
            self.primary_server_type = primary_server['type']
        else:
            self.primary_server_type = None
            
        self.detected_servers = detected
        return detected
        
    def get_process_priority(self, server_type):
        """Get priority for server types (higher = more important)"""
        priorities = {
            'xampp': 100,      # Highest - full stack
            'wamp': 90,        # High - full stack  
            'mamp': 85,        # High - full stack
            'nginx': 70,       # Medium - dedicated web server
            'iis': 65,         # Medium - dedicated web server
            'apache': 50,      # Lower - could be part of something else
            'mysql': 10        # Lowest - just database
        }
        return priorities.get(server_type, 0)

        """Detect running web servers"""
        print("[INFO] Detecting web servers...")
        
        # Process names to look for
        server_processes = {
            'xampp-control.exe': 'XAMPP Control Panel',
            'httpd.exe': 'Apache HTTP Server',
            'nginx.exe': 'Nginx Web Server',
            'nginx': 'Nginx Web Server',
            'w3wp.exe': 'IIS Web Server',
            'wampmanager.exe': 'WAMP Server',
            'mysqld.exe': 'MySQL Server',
            'mysqld': 'MySQL Server'
        }
        
        detected = []
        
        # Check running processes
        for proc in psutil.process_iter(['pid', 'name', 'exe']):
            try:
                proc_name = proc.info['name'].lower()
                for server_proc, server_name in server_processes.items():
                    if server_proc.lower() in proc_name:
                        detected.append({
                            'name': server_name,
                            'process': proc.info['name'],
                            'pid': proc.info['pid'],
                            'exe': proc.info['exe']
                        })
            except (psutil.NoSuchProcess, psutil.AccessDenied):
                continue
                
        # Check for common web server ports
        web_ports = [80, 443, 8080, 3000, 8000]
        active_ports = []
        
        for port in web_ports:
            if self.is_port_open('localhost', port):
                active_ports.append(port)
                
        print(f"[DETECTED] Active web server processes: {len(detected)}")
        for server in detected:
            print(f"  - {server['name']} (PID: {server['pid']})")
            
        print(f"[DETECTED] Active web ports: {active_ports}")
        
        self.detected_servers = detected
        return detected
        
    def is_port_open(self, host, port):
        """Check if a port is open"""
        try:
            with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
                sock.settimeout(1)
                result = sock.connect_ex((host, port))
                return result == 0
        except:
            return False
            
    def get_installation_type(self):
        """Ask user for installation type"""
        print("\n" + "=" * 40)
        print("INSTALLATION TYPE")
        print("=" * 40)
        print("1. Web Version Only (includes Court Tablet)")
        print("2. FiveM Version Only") 
        print("3. Both Versions")
        print()
        
        while True:
            choice = input("Select installation type (1-3): ").strip()
            if choice in ['1', '2', '3']:
                return int(choice)
            print("Invalid choice. Please enter 1, 2, or 3.")
            
    def get_web_server_type(self):
        """Determine web server type and get installation path"""
        print("\n" + "=" * 40)
        print("WEB SERVER CONFIGURATION")
        print("=" * 40)
        
        # Try to auto-detect based on primary server type
        if hasattr(self, 'primary_server_type') and self.primary_server_type:
            detected_type = self.primary_server_type
            
            # Skip mysql as it's not a web server
            if detected_type != 'mysql':
                print(f"[AUTO-DETECTED] {detected_type.upper()} server")
                use_detected = input(f"Use detected {detected_type.upper()} server? (y/n): ").lower()
                if use_detected == 'y':
                    return self.get_web_root_path(detected_type)
        
        # Manual selection if auto-detection failed or user declined
        print("Available web servers:")
        print("1. XAMPP")
        print("2. WAMP")
        print("3. MAMP")
        print("4. Nginx")
        print("5. Apache")
        print("6. Custom path")
        
        while True:
            choice = input("Select web server (1-6): ").strip()
            if choice == '1':
                return self.get_web_root_path('xampp')
            elif choice == '2':
                return self.get_web_root_path('wamp')
            elif choice == '3':
                return self.get_web_root_path('mamp')
            elif choice == '4':
                return self.get_web_root_path('nginx')
            elif choice == '5':
                return self.get_web_root_path('apache')
            elif choice == '6':
                return self.get_custom_web_path()
            print("Invalid choice. Please enter 1-6.")
        """Determine web server type and get installation path"""
        print("\n" + "=" * 40)
        print("WEB SERVER CONFIGURATION")
        print("=" * 40)
        
        # Try to auto-detect based on running processes
        detected_type = None
        for server in self.detected_servers:
            if 'xampp' in server['name'].lower():
                detected_type = 'xampp'
                break
            elif 'wamp' in server['name'].lower():
                detected_type = 'wamp'
                break
            elif 'nginx' in server['name'].lower():
                detected_type = 'nginx'
                break
            elif 'apache' in server['name'].lower():
                detected_type = 'apache'
                break
                
        if detected_type:
            print(f"[AUTO-DETECTED] {detected_type.upper()} web server")
            use_detected = input(f"Use detected {detected_type.upper()} server? (y/n): ").lower()
            if use_detected == 'y':
                return self.get_web_root_path(detected_type)
                
        # Manual selection
        print("Available web servers:")
        print("1. XAMPP")
        print("2. WAMP")
        print("3. MAMP")
        print("4. Nginx")
        print("5. Apache")
        print("6. Custom path")
        
        while True:
            choice = input("Select web server (1-6): ").strip()
            if choice == '1':
                return self.get_web_root_path('xampp')
            elif choice == '2':
                return self.get_web_root_path('wamp')
            elif choice == '3':
                return self.get_web_root_path('mamp')
            elif choice == '4':
                return self.get_web_root_path('nginx')
            elif choice == '5':
                return self.get_web_root_path('apache')
            elif choice == '6':
                return self.get_custom_web_path()
            print("Invalid choice. Please enter 1-6.")
            
    def get_web_root_path(self, server_type):
        """Get web root path for server type"""
        system = platform.system().lower()
        if system == 'darwin':
            system = 'mac'
        elif system not in ['windows', 'linux']:
            system = 'linux'  # Default fallback
            
        if server_type in self.web_roots and system in self.web_roots[server_type]:
            default_path = self.web_roots[server_type][system]
            
            # Handle wildcard paths (like WAMP with version numbers)
            if '*' in default_path:
                import glob
                matches = glob.glob(default_path)
                if matches:
                    default_path = matches[0]
                    
            if os.path.exists(default_path):
                print(f"[FOUND] Default web root: {default_path}")
                use_default = input("Use this path? (y/n): ").lower()
                if use_default == 'y':
                    return default_path, server_type
                    
        return self.get_custom_web_path()
        
    def get_custom_web_path(self):
        """Get custom web root path"""
        while True:
            path = input("Enter web root directory path: ").strip()
            if os.path.exists(path) and os.path.isdir(path):
                return path, 'custom'
            print("Path does not exist or is not a directory. Please try again.")
            
    def get_fivem_path(self):
        """Get FiveM server resources path"""
        print("\n" + "=" * 40)
        print("FIVEM SERVER CONFIGURATION")
        print("=" * 40)
        
        while True:
            path = input("Enter FiveM server resources directory path: ").strip()
            if os.path.exists(path) and os.path.isdir(path):
                return path
            print("Path does not exist or is not a directory. Please try again.")
            
    def install_web_version(self, web_root, include_courttablet=True):
        """Install web version files"""
        print(f"\n[INFO] Installing Web Version to: {web_root}")
        
        source_dir = Path("Web Version")
        target_dir = Path(web_root) / "court-system"
        
        if not source_dir.exists():
            print("[ERROR] Web Version source directory not found!")
            return False
            
        try:
            # Create target directory
            target_dir.mkdir(exist_ok=True)
            
            # Copy all files and directories from Web Version
            print("[INFO] Copying Web Version files...")
            for item in source_dir.iterdir():
                if item.is_file():
                    shutil.copy2(item, target_dir)
                    print(f"  Copied: {item.name}")
                elif item.is_dir():
                    target_subdir = target_dir / item.name
                    if target_subdir.exists():
                        shutil.rmtree(target_subdir)
                    shutil.copytree(item, target_subdir)
                    print(f"  Copied directory: {item.name}")
            
            # ALWAYS copy courttablet - it's required for the system to work
            courttablet_source = Path("HTML FiveM Version/courttablet")
            if courttablet_source.exists():
                print("[INFO] Copying Court Tablet files (REQUIRED)...")
                courttablet_target = target_dir / "courttablet"
                
                if courttablet_target.exists():
                    shutil.rmtree(courttablet_target)
                shutil.copytree(courttablet_source, courttablet_target)
                print(f"  Copied directory: courttablet")
                
                print("[SUCCESS] Web Version with Court Tablet installed successfully!")
                print(f"[INFO] Main Access URL: http://localhost/court-system/")
                print(f"[INFO] Court Tablet URL: http://localhost/court-system/courttablet/")
            else:
                print("[ERROR] HTML FiveM Version/courttablet directory not found!")
                print("[ERROR] This is REQUIRED for the system to work properly!")
                return False
                    
            return True
            
        except Exception as e:
            print(f"[ERROR] Failed to install Web Version: {e}")
            return False
            
    def install_fivem_version(self, fivem_path, web_root=None):
        """Install FiveM version files"""
        print(f"\n[INFO] Installing FiveM Version...")
        
        success = True
        
        # Install the FiveM resource (for in-game)
        fivem_source = Path("FiveM/sd-tablet")
        fivem_target = Path(fivem_path) / "sd-tablet"
        
        if fivem_source.exists():
            try:
                print(f"[INFO] Installing FiveM resource to: {fivem_target}")
                if fivem_target.exists():
                    shutil.rmtree(fivem_target)
                shutil.copytree(fivem_source, fivem_target)
                print("[SUCCESS] FiveM resource installed!")
            except Exception as e:
                print(f"[ERROR] Failed to install FiveM resource: {e}")
                success = False
        else:
            print("[ERROR] FiveM/sd-tablet directory not found!")
            success = False
                
        # Install the HTML FiveM Version (REQUIRED for FiveM to work)
        html_source = Path("HTML FiveM Version")
        if html_source.exists():
            if not web_root:
                # If no web root provided, ask for it
                print("\n[REQUIRED] FiveM needs web server files to function properly.")
                web_root, _ = self.get_web_server_type()
                
            try:
                print(f"[INFO] Installing HTML FiveM Version to web server: {web_root}")
                
                # Copy all contents from HTML FiveM Version to web root
                for item in html_source.iterdir():
                    if item.is_file():
                        target_file = Path(web_root) / item.name
                        shutil.copy2(item, target_file)
                        print(f"  Copied: {item.name}")
                    elif item.is_dir():
                        target_dir = Path(web_root) / item.name
                        if target_dir.exists():
                            shutil.rmtree(target_dir)
                        shutil.copytree(item, target_dir)
                        print(f"  Copied directory: {item.name}")
                        
                print("[SUCCESS] HTML FiveM Version installed to web server!")
                print(f"[INFO] Web Interface URL: http://localhost/{html_source.name}/")
                
            except Exception as e:
                print(f"[ERROR] Failed to install HTML FiveM Version: {e}")
                success = False
        else:
            print("[ERROR] HTML FiveM Version directory not found!")
            print("[ERROR] This is REQUIRED for FiveM version to work!")
            success = False
                
        return success
        """Install FiveM version files"""
        print(f"\n[INFO] Installing FiveM Version to: {fivem_path}")
        
        # Install the FiveM resource
        fivem_source = Path("FiveM/sd-tablet")
        fivem_target = Path(fivem_path) / "sd-tablet"
        
        if fivem_source.exists():
            try:
                if fivem_target.exists():
                    shutil.rmtree(fivem_target)
                shutil.copytree(fivem_source, fivem_target)
                print("[SUCCESS] FiveM resource installed!")
            except Exception as e:
                print(f"[ERROR] Failed to install FiveM resource: {e}")
                return False
                
        # Install the HTML FiveM Version
        html_source = Path("HTML FiveM Version/courttablet")
        html_target = Path(fivem_path).parent / "courttablet"  # Assuming web server integration
        
        if html_source.exists():
            try:
                if html_target.exists():
                    shutil.rmtree(html_target)
                shutil.copytree(html_source, html_target)
                print("[SUCCESS] FiveM HTML interface installed!")
            except Exception as e:
                print(f"[ERROR] Failed to install FiveM HTML interface: {e}")
                return False
                
        return True
        
    def find_mysql_executable(self, server_type):
        """Find MySQL executable based on server type"""
        system = platform.system().lower()
        if system == 'darwin':
            system = 'mac'
        elif system not in ['windows', 'linux']:
            system = 'linux'
            
        # Try server-specific paths first
        if server_type in self.mysql_paths and system in self.mysql_paths[server_type]:
            mysql_path = self.mysql_paths[server_type][system]
            
            # Handle wildcard paths
            if '*' in mysql_path:
                import glob
                matches = glob.glob(mysql_path)
                if matches:
                    mysql_path = matches[0]
                    
            if os.path.exists(mysql_path):
                return mysql_path
                
        # Try system MySQL
        system_mysql = self.mysql_paths['system'][system]
        if shutil.which(system_mysql.replace('.exe', '') if system_mysql.endswith('.exe') else system_mysql):
            return system_mysql
            
        return None
        
    def install_database(self, server_type):
        """Install database schema"""
        print("\n" + "=" * 40)
        print("DATABASE INSTALLATION")
        print("=" * 40)
        
        install_db = input("Would you like to install the database? (y/n): ").lower()
        if install_db != 'y':
            print("[SKIPPED] Database installation skipped.")
            return True
            
        # Get database credentials
        print("\nEnter MySQL database credentials:")
        db_host = input("Host (default: localhost): ").strip() or "localhost"
        db_port = input("Port (default: 3306): ").strip() or "3306"
        db_user = input("Username (default: root): ").strip() or "root"
        db_password = input("Password: ").strip()
        
        try:
            # Import mysql.connector here to avoid import issues
            import mysql.connector
            from mysql.connector import Error
            
            # Connect to MySQL
            print("[INFO] Connecting to MySQL...")
            connection = mysql.connector.connect(
                host=db_host,
                port=int(db_port),
                user=db_user,
                password=db_password
            )
            
            cursor = connection.cursor()
            
            # Drop and recreate database to avoid conflicts
            print("[INFO] Creating fresh courtsystem database...")
            cursor.execute("DROP DATABASE IF EXISTS courtsystem")
            cursor.execute("CREATE DATABASE courtsystem")
            cursor.execute("USE courtsystem")
            
            # Find and execute SQL file
            sql_files = [
                Path("Web Version/sql/courtsystem.sql"),
                Path("HTML FiveM Version/SQL/courtsystem.sql"),
                Path("SQL/courtsystem.sql")
            ]
            
            sql_file = None
            for file_path in sql_files:
                if file_path.exists():
                    sql_file = file_path
                    break
                    
            if not sql_file:
                print("[ERROR] Could not find courtsystem.sql file!")
                return False
                
            print(f"[INFO] Executing SQL file: {sql_file}")
            
            # Read and execute SQL file with better parsing
            with open(sql_file, 'r', encoding='utf-8') as f:
                sql_content = f.read()
                
            # Better SQL parsing - handle multi-line statements
            sql_commands = []
            current_command = ""
            
            for line in sql_content.split('\n'):
                line = line.strip()
                # Skip comments and empty lines
                if line.startswith('--') or line.startswith('#') or not line:
                    continue
                    
                current_command += line + " "
                
                # If line ends with semicolon, it's end of command
                if line.endswith(';'):
                    sql_commands.append(current_command.strip())
                    current_command = ""
            
            # Execute commands
            successful_commands = 0
            for i, command in enumerate(sql_commands):
                if command:
                    try:
                        cursor.execute(command)
                        successful_commands += 1
                    except mysql.connector.Error as e:
                        error_msg = str(e).lower()
                        # Only show warnings for non-critical errors
                        if any(skip_error in error_msg for skip_error in [
                            "already exists", "duplicate", "multiple primary key"
                        ]):
                            print(f"[INFO] Skipping duplicate/existing: Command {i+1}")
                        else:
                            print(f"[WARNING] SQL command {i+1} failed: {e}")
                            
            connection.commit()
            print(f"[SUCCESS] Database installed successfully! ({successful_commands}/{len(sql_commands)} commands executed)")
            
            # Update database configuration in PHP files
            self.update_database_config(db_host, db_port, db_user, db_password)
            
            cursor.close()
            connection.close()
            return True
            
        except mysql.connector.Error as e:
            print(f"[ERROR] Database installation failed: {e}")
            return False
        except Exception as e:
            print(f"[ERROR] Unexpected error during database installation: {e}")
            return False
            
    def update_database_config(self, host, port, user, password):
        """Update database configuration in PHP files"""
        print("[INFO] Updating database configuration files...")
        
        # Database configuration template
        db_config = f'''<?php
// Database configuration - Auto-generated by installer
$host = '{host}';
$port = '{port}';
$username = '{user}';
$password = '{password}';
$database = 'courtsystem';

try {{
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}} catch(PDOException $e) {{
    die("Connection failed: " . $e->getMessage());
}}
?>'''

        # Find and update database.php files
        if self.web_root:
            db_files = [
                "include/database.php",
                "courttablet/include/database.php"
            ]
            
            for db_file in db_files:
                file_path = Path(self.web_root) / "court-system" / db_file
                try:
                    file_path.parent.mkdir(parents=True, exist_ok=True)
                    with open(file_path, 'w') as f:
                        f.write(db_config)
                    print(f"  Updated: {db_file}")
                except Exception as e:
                    print(f"  [WARNING] Could not update {db_file}: {e}")
        else:
            print("  [WARNING] Web root not set, skipping database config update")
                    
    def create_installation_summary(self, install_type, web_root=None, fivem_path=None):
        """Create installation summary"""
        print("\n" + "=" * 60)
        print("INSTALLATION SUMMARY")
        print("=" * 60)
        
        print(f"Installation Date: {time.strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"Installation Type: {['', 'Web Only', 'FiveM Only', 'Both'][install_type]}")
        
        if web_root:
            print(f"Web Installation Path: {web_root}/court-system")
            print(f"Web Access URL: http://localhost/court-system/")
            
        if fivem_path:
            print(f"FiveM Resource Path: {fivem_path}/sd-tablet")
            
        print("\nNext Steps:")
        if install_type in [1, 3]:  # Web version installed
            print("1. Start your web server if not already running")
            print("2. Access the application at: http://localhost/court-system/")
            print("3. Register an admin account")
            print("4. Configure user permissions as needed")
            
        if install_type in [2, 3]:  # FiveM version installed
            print("1. Add 'sd-tablet' to your server.cfg resources")
            print("2. Restart your FiveM server")
            print("3. Configure the tablet resource settings")
            
        print("\nSupport:")
        print("- Check README.md for detailed configuration instructions")
        print("- Ensure proper file permissions are set")
        print("- Verify database connection settings")
        
    def cleanup_temp_files(self):
        """Clean up temporary files"""
        temp_files = [
            "installer.log",
            "temp_install.txt"
        ]
        
        for temp_file in temp_files:
            try:
                if os.path.exists(temp_file):
                    os.remove(temp_file)
            except:
                pass
                
    def run_installer(self):
        """Main installer routine"""
        try:
            self.print_banner()
            
            # Detect web servers
            detected = self.detect_web_servers()
            
            if not detected:
                print("[WARNING] No web servers detected running.")
                print("Make sure your web server is running before installation.")
                continue_anyway = input("Continue anyway? (y/n): ").lower()
                if continue_anyway != 'y':
                    print("Installation cancelled.")
                    return False
                    
            # Get installation type
            install_type = self.get_installation_type()
            
            web_root = None
            fivem_path = None
            server_type = None
            
            # Handle web installation
            if install_type in [1, 3]:  # Web or Both
                web_root, server_type = self.get_web_server_type()
                self.web_root = web_root  # Store for database config
                
                if not self.install_web_version(web_root):
                    print("[ERROR] Web installation failed!")
                    return False
                    
            # Handle FiveM installation  
            if install_type in [2, 3]:  # FiveM or Both
                fivem_path = self.get_fivem_path()
                
                # For FiveM installation, we need web server info too
                if install_type == 2:  # FiveM only
                    # Pass None for web_root so install_fivem_version will ask for it
                    if not self.install_fivem_version(fivem_path, None):
                        print("[ERROR] FiveM installation failed!")
                        return False
                else:  # Both versions
                    # Use the web_root we already have
                    if not self.install_fivem_version(fivem_path, web_root):
                        print("[ERROR] FiveM installation failed!")
                        return False
                    
            # Install database
            if not self.install_database(server_type or 'custom'):
                print("[WARNING] Database installation failed, but files were copied successfully.")
                print("You can manually import the SQL file later.")
                
            # Create summary
            self.create_installation_summary(install_type, web_root, fivem_path)
            
            print("\n[SUCCESS] Installation completed successfully!")
            return True
            
        except KeyboardInterrupt:
            print("\n[CANCELLED] Installation cancelled by user.")
            return False
        except Exception as e:
            print(f"\n[ERROR] Installation failed: {e}")
            return False
        finally:
            self.cleanup_temp_files()

def check_requirements():
    """Check if required Python packages are installed"""
    required_packages = {
        'psutil': 'psutil',
        'mysql.connector': 'mysql-connector-python'
    }
    
    missing_packages = []
    
    for import_name, package_name in required_packages.items():
        try:
            __import__(import_name)
        except ImportError:
            missing_packages.append(package_name)
            
    if missing_packages:
        print("Missing required packages:")
        for package in missing_packages:
            print(f"  - {package}")
        print("\nInstall missing packages with:")
        print(f"pip install {' '.join(missing_packages)}")
        return False
        
    return True

def main():
    """Main entry point"""
    print("Court System Installer")
    print("Checking requirements...")
    
    if not check_requirements():
        print("\nPlease install required packages and run the installer again.")
        input("Press Enter to exit...")
        return
        
    # Check if running from correct directory
    required_dirs = ['Web Version', 'HTML FiveM Version', 'FiveM']
    missing_dirs = [d for d in required_dirs if not os.path.exists(d)]
    
    if missing_dirs:
        print(f"\n[ERROR] Missing required directories: {', '.join(missing_dirs)}")
        print("Please run the installer from the Court System root directory.")
        input("Press Enter to exit...")
        return
        
    # Run installer
    installer = CourtSystemInstaller()
    success = installer.run_installer()
    
    if success:
        print("\nInstallation completed! Press Enter to exit...")
    else:
        print("\nInstallation failed! Press Enter to exit...")
        
    input()

if __name__ == "__main__":
    main()