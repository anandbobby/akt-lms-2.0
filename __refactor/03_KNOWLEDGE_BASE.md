# Refactor 2.0: Project Knowledge Base

**Document Purpose:** A repository of hard-won facts, critical insights, and permanent solutions that have been validated and must be respected throughout the project.

---

### 1. Permanent Code Fixes

The following modifications **must be manually applied** to the pristine codebase **before** the first installation attempt to prevent known failures.

#### **Fix #1: AKT Plugin Installation Order Dependency**

* **Issue**: The installation fails with a fatal error: `Capability 'local/iomad_learningpath:manage' was not found!`.
* **Root Cause**: The `local/akt` plugin's installation script (`install.php`) attempts to modify permissions for the `local/iomad_learningpath` plugin before it has been installed, so the capability does not yet exist.
* **✅ Permanent Solution**: Add an existence check before attempting to modify the capability.

* **Action Required**:
    1.  Open the file: `local/akt/db/install.php`
    2.  Locate the section related to unassigning capabilities (around lines 39-45).
    3.  Replace the original lines with this code snippet:

    ```php
    // FIX: Only unassign learning path capabilities if the plugin and its capabilities actually exist.
    // This prevents a fatal error during initial installation if local/akt installs before local/iomad_learningpath.
    if (file_exists($CFG->dirroot . '/local/iomad_learningpath/lib.php')) {
        $capabilities = get_capability_info('local/iomad_learningpath:manage');
        if (!empty($capabilities)) {
            unassign_capability('local/iomad_learningpath:manage', $authrole->id);
            unassign_capability('local/iomad_learningpath:view', $authrole->id);
        }
    }
    ```
    4. Save the file and commit this change before running the installer.

---

### 2. Established Environment Facts

These are known truths about the environment. They are handled by our Docker setup and require no action, only awareness.

* **Fact**: IOMAD has complex forms and requires `max_input_vars=5000` and `memory_limit=512M`.
    * **Status**: ✅ **AUTOMATICALLY HANDLED** by the `php.ini` configuration in our Docker environment.

* **Fact**: The Moodle installer may display a warning about `mysqli` vs `mariadb` drivers.
    * **Status**: ✅ **SAFE TO IGNORE**. Our environment is correctly configured to use the `mariadb` driver. Proceed with the installation.

* **Fact**: The Moodle `config.php` file requires a `dbtype` of `mariadb`.
    * **Status**: ✅ **AUTOMATICALLY HANDLED** by the installation wizard when you select "MariaDB" as the database type.
