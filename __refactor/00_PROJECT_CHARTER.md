AKTREA LMS Refactoring: Project Charter 2.0
Document Purpose: This charter defines the mission, objectives, and high-level strategy for the "Refactor 2.0" initiative. It is the single source of truth for the project's purpose and direction.

1. Mission Statement
   To systematically refactor the AKTREA LMS codebase, creating a lean, stable, and professionally branded application. This effort will produce a portable system ready for initial testing and eventual deployment to a GCP environment, serving as a practical learning project.

2. Core Objectives
   Objective

Key Success Metric

Establish Stability

Achieve a 100% successful, repeatable installation on a pristine codebase.

System Optimization

Systematically reduce complexity by removing 20-30% of unused Moodle components.

Professional Branding

Remove all user-facing Moodle branding and language safely and incrementally.

Ensure Portability

Maintain a simple, Dockerized environment that can be easily "lifted and shifted."

Preserve Functionality

Guarantee zero regressions in core IOMAD and custom AKT functionality.

3. Guiding Principles
   Clean Slate First: We begin with a pristine, un-modified clone of the original codebase. No historical baggage is carried forward, only knowledge.

Working First, Optimize Second: A fully functional, tagged "Golden Baseline" must be established before any optimization or de-branding is attempted.

Stability Over Speed: Every change will be methodical and validated. We will sacrifice speed for the certainty of a stable system at every step.

Document As We Go: The REFACTORING_LOG.md will be updated with every significant action, creating a clear audit trail.

4. High-Level Phases
   Phase 0: Project Initialization

Clone the original LMS source code into a new directory.

Sever all previous Git history and initialize a new, clean repository.

Commit the pristine code and this foundational documentation.

Phase 1: Environment & Golden Baseline

Build the portable and configurable Docker environment using the .env file pattern.

Apply all known, permanent code fixes from the KNOWLEDGE_BASE.md.

Execute a complete, successful end-to-end installation.

Thoroughly test core functionality.

Tag the successful state as v2.0-working-baseline.

Phase 2: Systematic Optimization

Conduct a full dependency analysis of all target components for removal.

Execute the "One-by-One" removal protocol as defined in the OPERATING_MANUAL.md.

Prioritize low-risk components first.

Test and validate the system after every single component removal.

Phase 3: Cosmetic De-branding

To be performed only after Phase 2 is complete.

Isolate all changes in feature branches.

Focus on targeted template and language file updates.

Perform visual regression testing for every change.

Phase 4: Portable Deployment (Future Goal)

Once the LMS is stable and optimized locally, deploy the entire Dockerized environment to a single GCP Compute Engine VM.

Validate functionality in the cloud, fulfilling the project's learning objective.
