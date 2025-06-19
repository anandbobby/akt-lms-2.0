Refactor 2.0: Standard Operating Manual
Document Purpose: This manual contains the non-negotiable rules and protocols for the project. Adherence is mandatory to ensure consistency, prevent errors, and guarantee success.

1. The Three Absolutes
   These principles govern every action taken in this repository.

The Golden Baseline is Sacred. The v2.0-working-baseline tag is our immutable starting point. No optimizations or changes are ever committed before this baseline is established and verified.

One Change, One Test, One Commit. Never bundle unrelated changes. Every single component removal or significant fix must be in its own commit and must be followed by a full, successful installation test from scratch.

Document Before, During, and After. All plans, analyses, and results must be documented in the appropriate file within the \_refactor/ directory.

2. Git & Branching Protocol
   main Branch: This branch is for stable, tagged releases ONLY (e.g., v2.0-working-baseline). Direct commits to main are strictly forbidden. All changes must come from approved, tested feature branches via pull requests.

Feature Branches: All work—including component removals, bug fixes, and de-branding—must be done on descriptive feature branches (e.g., feature/remove-mod-chat, fix/akt-capability-check).

3. The Component Removal Protocol
   This is the mandatory, step-by-step process for removing any component.

Analyze: Before touching any code, document the target component's dependencies in \_refactor/COMPONENT_ANALYSIS.md.

Branch: Create a new feature branch for the removal (e.g., feature/remove-block-rss-client).

Archive: Move the component's directory from its location into the \_archive/ directory. Do not delete it.

Commit: Commit this single change with a clear message (e.g., refactor: Archive block-rss-client for removal test).

Re-Install & Test: THIS IS THE MOST CRITICAL STEP.

Destroy the existing Docker containers and volumes (docker-compose down -v).

Start fresh (docker-compose up -d --build).

Perform a complete, end-to-end installation from the wizard.

Run a full suite of functional tests.

Evaluate and Document:

Success: If the installation and tests pass, merge the branch into main. Log the successful removal in REFACTORING_LOG.md.

Failure: If any error occurs, abandon the branch immediately. Document the discovered dependency in \_refactor/COMPONENT_ANALYSIS.md. Do not attempt to fix it; the failed test is the documentation.

4. Documentation & Deletion Protocol
   Single Source of Truth: All project documentation lives exclusively in the \_refactor/ directory. Do not create documentation elsewhere.

The Archive is The Only Bin: No file or folder is ever permanently deleted. To "delete," move the item to the \_archive/ directory. This ensures everything is recoverable.
