# CiviTrack - Citizen Life Records Management System

A comprehensive web application for tracking and managing citizen life records including tax records, employment records, and criminal records. The system supports three user types: Citizens, Police Officers, and Administrators.

## Features

### For Citizens
- View personal information (name, email, address, phone)
- Update personal information (email, name, address, phone only)
- View tax records, employment records, and criminal records (read-only)
- Apply for passport and driving license
- Track application status and document status
- Cannot view other citizens' information

### For Police Officers
- Search citizens by NID
- View all citizen information and records
- Update criminal records (cannot delete)
- Verify and approve/reject passport and driving license applications
- Add new criminal records

### For Administrators
- Full access to all citizen records
- Search citizens by NID
- Filter citizens by name and address
- Add, update, and delete all types of records
- Mark citizens as deceased (moves to deceased table)
- Manage all aspects of the system

## Database Schema

The system uses MySQL with the following main tables:

- `users` - User authentication and roles
- `citizen` - Citizen personal information
- `police` - Police officer information
- `admin` - Administrator information
- `tax_record` - Tax payment records
- `employment_record` - Employment history
- `criminal_record` - Criminal case records
- `passport` - Passport information
- `driving_license` - Driving license information
- `passport_application` - Passport applications
- `license_application` - Driving license applications
- `deceased_citizen` - Deceased citizen records

## Installation

1. **Prerequisites**
   - XAMPP or similar local server environment
   - PHP 7.4 or higher
   - MySQL 5.7 or higher

2. **Setup**
   - Place all files in your XAMPP htdocs folder
   - Import the `civitrack.sql` file into your MySQL database
   - Update database connection settings in `db_connect.php` if needed

3. **Default Login Credentials**
   - Admin: username: `admin`, password: `password`
   - Police: username: `police1`, password: `password`
   - Citizen: username: `citizen1`, password: `password`

## File Structure

```
CiviTrack/
├── admin_dashboard.php      # Admin interface
├── citizen_dashboard.php    # Citizen interface
├── police_dashboard.php     # Police interface
├── login.php               # Login page
├── register.php            # Registration page
├── logout.php              # Logout handler
├── db_connect.php          # Database connection
├── db_config.php           # Database configuration
├── civitrack.sql           # Database schema
├── styles.css              # Common CSS styles
└── README.md               # This file
```

## Usage

1. **Login**
   - Navigate to `login.php`
   - Enter credentials for your user type
   - You'll be redirected to the appropriate dashboard

2. **Citizen Operations**
   - View your personal records
   - Update personal information
   - Apply for documents
   - Track application status

3. **Police Operations**
   - Search citizens by NID
   - View citizen records
   - Update criminal records
   - Verify applications

4. **Admin Operations**
   - Search and filter citizens
   - Manage all records
   - Add new records
   - Mark citizens as deceased

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session management for user authentication
- Role-based access control
- Input validation and sanitization

## Technical Details

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Architecture**: MVC pattern with separation of concerns

## Sample Data

The database includes sample data for testing:
- One citizen with NID: 1234567890
- Sample tax, employment, and criminal records
- One pending passport application

## Browser Compatibility

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support or questions, please contact the development team.

## Changelog

### Version 1.0.0
- Initial release
- Complete citizen record management system
- Three-tier user access (Citizen, Police, Admin)
- Document application system
- Responsive design
- Security features implemented