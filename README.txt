===============================================================================
                          AKTREA CUSTOM LMS SYSTEM
                    Core LMS (IOMAD-based) + Custom Extensions
===============================================================================

PROJECT OVERVIEW
================

This is a highly customized Learning Management System built on three distinct
layers of code, each providing specific functionality for corporate training
delivery and multi-tenant management.

ARCHITECTURE LAYERS
==================

┌─────────────────────────────────────────────────────────────────────────────┐
│                           LAYER 3: CUSTOM AKT CODE                         │
│  • Enhanced user management with bulk operations                            │
│  • Custom role restrictions and permission controls                         │
│  • Bulk course completion management tools                                  │
│  • Company-specific authentication controls                                 │
│  • Advanced logging and tracking systems                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                              LAYER 2: IOMAD                                │
│  • Multi-company/tenant management system                                   │
│  • Corporate training licensing and commerce                                │
│  • Company managers and department manager roles                            │
│  • Advanced corporate reporting and analytics                               │
│  • Training event management and certificates                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                           LAYER 1: CORE LMS (BASE SYSTEM)                           │
│  • Core Learning Management System                                          │
│  • Course creation and content management                                   │
│  • User authentication and basic role management                            │
│  • Activities, assessments, and standard reporting                          │
└─────────────────────────────────────────────────────────────────────────────┘

LAYER 1: CORE LMS (BASE SYSTEM)
====================================

Base Platform Version: 4.2.6 (Build: 20240212)
Branch: 402 (Stable)
Technology Stack:
- PHP 8.0+
- PostgreSQL/MySQL/MariaDB/SQL Server
- JavaScript ES6+, SCSS/CSS3
- Node.js build tools (Grunt, Babel)

Core Components:
- /admin/          - System administration
- /course/         - Course management
- /user/           - User management
- /mod/            - Activity modules (quiz, assign, forum, etc.)
- /blocks/         - Dashboard blocks
- /theme/          - UI themes
- /lib/            - Core libraries

LAYER 2: IOMAD CORPORATE EXTENSIONS
===================================

IOMAD (Intelligent Open-source Moodle for Advanced Delivery) transforms
the standard LMS into a multi-tenant corporate training platform.

Key IOMAD Components:

BLOCKS:
- /blocks/iomad_company_admin/     - Central company management interface
- /blocks/iomad_commerce/          - E-commerce and payment processing
- /blocks/iomad_company_selector/  - Company/tenant switching
- /blocks/iomad_reports/           - Advanced corporate reporting
- /blocks/iomad_learningpath/      - Learning pathway management
- /blocks/iomad_microlearning/     - Microlearning content delivery

LOCAL PLUGINS:
- /local/iomad/                    - Core IOMAD functionality
- /local/email/                    - Enhanced email management
- /local/email_reports/            - Automated email reporting
- /local/iomad_learningpath/       - Learning path management
- /local/iomad_track/              - User progress tracking
- /local/report_*/                 - Corporate reporting suite

ACTIVITIES:
- /mod/iomadcertificate/           - Company-branded certificates
- /mod/trainingevent/              - Face-to-face training sessions
- /mod/reengagement/               - Automated follow-up workflows

ENROLLMENT:
- /enrol/license/                  - License-based course access

CORPORATE ROLE SYSTEM:
- Client Administrator             - Highest level (creates companies)
- Company Manager                  - Company-level management
- Company Department Manager       - Department-level management
- Company Course Editor            - Course content management
- Company Reporter                 - Reporting access only

LAYER 3: CUSTOM AKT MODIFICATIONS
=================================

Our custom layer adds sophisticated user management, bulk operations,
and company-specific workflow enhancements.

Custom Plugin: /local/akt/
- Version: 0.1 (Build: 2024031001)
- Purpose: Aktrea-specific customizations

KEY CUSTOM FEATURES:

1. ENHANCED USER MANAGEMENT
   - Bulk user creation with CSV upload
   - Advanced error handling and logging
   - Custom user role assignment workflows
   - Enhanced permission controls

2. BULK COURSE COMPLETION SYSTEM
   File: /local/akt/cc.php
   - Mass course completion marking via CSV
   - Automated completion validation
   - Custom logging and audit trails
   - Error reporting and recovery

3. CUSTOM DATABASE TABLES
   - {iomad_bulk_upload}              - Tracks bulk operation progress
   - {oauth2_issuer} modifications    - Company-specific OAuth integration

4. ENHANCED ROLE RESTRICTIONS
   - Modified Company Department Manager permissions
   - Custom capability assignments
   - Company-specific access controls

5. AUTHENTICATION CONTROLS
   - Hide login form functionality
   - Company-specific login workflows
   - OAuth2 automatic license mapping

6. LOGGING AND TRACKING
   - /blocks/iomad_company_admin/upload_logs.php - Upload history
   - Enhanced progress tracking for bulk operations
   - Custom file processing and download capabilities

CRITICAL CUSTOM FILES:
- /local/akt/cc.php                - Bulk completion management
- /local/akt/settings.php          - Custom configuration options
- /local/akt/db/install.php        - Database modifications
- Modified upload workflows in /blocks/iomad_company_admin/

SYSTEM REQUIREMENTS
==================

PHP Extensions Required:
- php >= 8.0.0
- ext-iconv, ext-mbstring, ext-curl, ext-openssl
- ext-ctype, ext-zip, ext-zlib, ext-gd
- ext-simplexml, ext-spl, ext-pcre, ext-dom
- ext-xml, ext-xmlreader, ext-intl, ext-json
- ext-hash, ext-fileinfo, ext-sodium

Database Support:
- PostgreSQL (recommended)
- MySQL/MariaDB
- Microsoft SQL Server
- Oracle (via ext-oci8)

Build Tools:
- Node.js >= 16.14.0 <17
- npm/yarn for dependency management
- Grunt for task automation

DEPLOYMENT ARCHITECTURE
======================

Multi-Tenant Structure:
- Single codebase serves multiple companies
- Company-specific branding and configurations
- Isolated data and user management per tenant
- Shared courses with company-specific access controls

Licensing Model:
- Course licenses can be purchased and allocated
- Users consume licenses upon enrollment
- Automatic license tracking and reporting
- Program-based licensing for course bundles

REFACTORING GOALS
================

Current project aims to streamline the system by:

1. COMPONENT REMOVAL
   - Unused authentication methods (CAS, LDAP, OAuth2 where not needed)
   - Redundant activity modules (chat, wiki, workshop)
   - Unnecessary blocks and repository plugins
   - Legacy features and deprecated functionality

2. OPTIMIZATION TARGETS
   - Remove unused language packs
   - Eliminate development tools from production
   - Clean up unused themes and UI components
   - Remove unnecessary documentation files

3. PERFORMANCE ENHANCEMENT
   - Streamline database queries
   - Optimize custom bulk operations
   - Improve caching mechanisms
   - Reduce memory footprint

IMPORTANT NOTES
==============

PRESERVATION REQUIREMENTS:
- ALL IOMAD components are essential for corporate functionality
- ALL custom AKT modifications are critical for business operations
- Company management and licensing systems must remain intact
- Custom role hierarchy and permissions are business-critical

MODIFICATION GUIDELINES:
- Test all changes in staging environment first
- Document any modifications to custom components
- Maintain compatibility between all three layers
- Preserve licensing and multi-tenancy functionality

DEVELOPMENT WORKFLOW:
- Changes should be tested layer by layer
- Custom modifications may override IOMAD defaults
- Database schema changes require careful migration planning
- Role and permission changes affect multiple system layers

SUPPORT INFORMATION
==================

System Contacts:
- IOMAD: Community support and documentation
- Core Platform: Community support and documentation
- Custom Code: Internal development team

Documentation References:
- IOMAD Documentation: https://www.iomad.org/
- Custom modifications: See /local/akt/ and related files

Version History:
- Core LMS: 4.2.6 (Stable branch 402)
- IOMAD: Compatible with Moodle 4.2.x
- Custom AKT: v0.1 (2024031001)

===============================================================================
                            END OF DOCUMENTATION
=============================================================================== 