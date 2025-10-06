# LLM.MD — Living Library of Methods & Meta-Development Principles

## 0. Requirements, Traceability, and Documentation

- **Automated Documentation Generation:**
  - Use phpDocumentor to generate HTML documentation for the project.
  - All classes must include UML diagrams describing the class structure, message flows, and variable passing.
  - UML and message flow diagrams should be included in phpdoc blocks or as referenced images in the documentation output.
  - Ensure documentation is updated as code changes, and that generated HTML docs are reviewed for completeness and accuracy.

- **Requirements Capture:**
  - All discussions and instructions in our chat should result in either functional or non-functional requirements.
  - Each requirement must be recorded in a traceability matrix, listing which class/function/module fulfills it.

- **Traceability Matrix:**
  - Maintain a matrix mapping requirements to implementation (class/function/file).
  - Update the matrix as code evolves to ensure all requirements are covered and testable.

- **Project Documentation Standards:**
  - Maintain the following documentation for every project:
    - Requirements Traceability Matrix
    - QA Test Plan (manual and automated)
    - UAT (User Acceptance Test) checklist for manual validation
    - PHPUnit (or equivalent) automated test suite
    - Architectural Design Documents
    - UML Class Diagrams
    - ERD (Entity Relationship Diagram) for data models
    - Message Flow and Program Flow diagrams
  - Keep documentation up to date as the project evolves.


## 1. Core Principles

- **Open/Closed Principle (OCP):**
  - Classes should be open for extension but closed for modification. Use interfaces, abstract classes, and event hooks to allow new features without changing existing code.
- **Interface Segregation Principle (ISP):**
  - Prefer several small, specific interfaces over large, general-purpose ones.
- **Liskov Substitution Principle (LSP):**
  - Subclasses should be substitutable for their base classes without altering the correctness of the program.
- **Separation of Concerns (SoC):**
  - Each module or layer should have a distinct responsibility (e.g., data access, business logic, presentation).
- **Immutability:**
  - Use immutable value objects for data that should not change after creation.
- **Graceful Degradation & Progressive Enhancement:**
  - Design UI and APIs to degrade gracefully if features are unavailable, and enhance when possible.
- **Internationalization (i18n) & Localization (l10n):**
  - Plan for multi-language support in UI and data models.
- **Accessibility (a11y):**
  - Ensure UI components are accessible to all users, including those using assistive technologies.
- **Performance Optimization:**
  - Profile and optimize code for speed and memory usage, especially in data-heavy or real-time applications.
- **Error Handling Strategy:**
  - Use consistent error handling patterns (exceptions, error objects, etc.) and document expected error flows.
- **Extensibility & Plugin Architecture:**
  - Design core systems to allow third-party plugins or modules to extend functionality without modifying core code.
- **Backward Compatibility:**
  - When possible, avoid breaking changes to public APIs and document any that are necessary.
- **Automated Code Quality Tools:**
  - Use static analysis, code sniffers, and linters (e.g., PHPStan, PHPCS) as part of CI/CD.

- **KISS (Keep It Simple, Stupid):**
  - Favor simple, clear solutions over clever or complex ones.
- **YAGNI (You Aren’t Gonna Need It):**
  - Don’t implement features until they are actually needed.
- **Fail Fast & Defensive Programming:**
  - Validate inputs early, throw exceptions on invalid state, and fail fast to catch bugs early.
- **Consistent Naming Conventions:**
  - Use clear, descriptive, and consistent names for classes, functions, and variables.
- **Code Reviews & Pair Programming:**
  - All significant changes should be reviewed by another developer.
- **Automated CI/CD:**
  - Set up continuous integration and deployment pipelines (e.g., GitHub Actions) to run tests and deploy on push.
- **Security by Design:**
  - Sanitize all inputs, use prepared statements, and follow least privilege principles.
- **Logging & Monitoring:**
  - Implement structured logging with configurable levels (error, warning, info, debug).
  - Logging level should be settable via config file or MySQL DB table.
- **Version Control Best Practices:**
  - Use feature branches, meaningful commit messages, and regular merges to main.
- **Documentation as Code:**
  - Keep documentation in version control, update alongside code changes.

- **Single Responsibility Principle (SRP):**
  - Each class or module should do one thing only.
  - Keep classes small, focused, and easy to test.
- **Minimalism:**
  - Use minimal variables and concise functions.
  - Avoid unnecessary complexity.
- **Dependency Injection:**
  - Pass dependencies into classes/functions instead of hard-coding them.
  - Enables easier testing and flexibility.
- **SOLID Principles:**
  - **S**ingle Responsibility
  - **O**pen/Closed
  - **L**iskov Substitution
  - **I**nterface Segregation
  - **D**ependency Inversion
- **DRY (Don’t Repeat Yourself):**
  - Reuse code, avoid duplication, and extract common logic.

## 2. Architectural Patterns

- **Event-Driven Architecture:**
  - For all but the simplest apps, use an event-driven architecture to decouple components and enable extensibility.
  - Design modules/classes to register themselves for the events or actions they handle.
  - The system should automatically discover and include new modules/classes, and remove those that are deleted, without manual wiring.
  - Modules should declare their responsibilities and event hooks at registration.
  - Where possible, mimic or interoperate with WordPress's event (hook/action/filter) system to maximize compatibility with WP and similar frameworks.
  - Ensure the event system can coexist with other frameworks (e.g., WP, FrontAccounting) and does not interfere with their core event handling.

- **REST/SOAP API Layer:**
  - Build REST (and/or SOAP) APIs for data access on top of DAOs and Models.
  - APIs should be framework-agnostic and reusable.
- **Database Abstraction Layer:**
  - Implement a DB layer that can interface with multiple frameworks (standalone, WordPress, FrontAccounting, etc.).
  - Design for drop-in replacement and easy integration.
- **PSR Compliance:**
  - Follow PHP-FIG PSRs (e.g., PSR-1, PSR-2, PSR-4, PSR-12, PSR-7, PSR-11, PSR-17) as closely as possible for code, autoloading, HTTP, containers, etc.
- **Database Migration/Update Scripts:**
  - Implement or reuse migration scripts (like FA/SuiteCRM) that compare current schema to new and apply changes safely.

- **MVC (Model-View-Controller):**
  - Use for clear separation of concerns.
  - Applies to standalone apps, WordPress plugins, and FrontAccounting modules.
- **Plugin/Extension Design:**
  - Place generic, reusable code in separate modules or Composer packages.
  - Keep app-specific business logic and UI separate from reusable libraries.
- **Framework-Agnostic Code:**
  - Write code that can be reused across different frameworks and projects.

## 3. Code Organization & Reuse

- **Composer Packages:**
  - Extract reusable components into Composer packages for sharing and maintenance.
- **Clear Boundaries:**
  - Distinguish between generic libraries and application-specific code.
- **Documentation:**
  - Document patterns, decisions, and reusable modules for onboarding and consistency.

## 4. Practical Guidelines

- **Custom Exceptions:**
  - Define and use custom exception classes for different error conditions instead of relying solely on generic exceptions.
  - Use exception hierarchies to represent different error types and enable precise error handling.
- **Replace If-Then-Else with Polymorphism:**
  - Where possible, refactor if-then-else or switch-case logic into class hierarchies (Strategy, State, Command, etc.) to eliminate the need for else statements.
  - This leads to more maintainable, extensible, and testable code, as advocated by Martin Fowler.

- **Test-Driven Development (TDD):**
  - Write the unit test for new functionality first. The test should initially fail.
  - Implement the new code so that the test passes.
  - Each code unit should indicate (in comments or docblocks) which requirement it fulfills.
- **UI/View Code Generation:**
  - Generate UI/View code as classes with `render()` functions for output.
  - Keep rendering logic separate from business logic.
- **Business Logic Composition:**
  - Business logic classes should compose (hold) other classes representing elemental types (e.g., int, double, string).
  - Elemental classes should have sanity checks in their setters to validate values.
  - The existing `ORIGIN` class with a `set(field, value, enforce)` signature can be reused or extended for these elemental types.

- **Refactoring and Code Migration:**
  - When refactoring, do not delete code from the old class until it is fully implemented in a new class with passing unit tests.
  - In the old class, leave a comment where the code was, explaining where the code has moved to.
  - This ensures traceability and safe migration of logic.
- **Documentation and Comment Preservation:**
  - Minimize changes to comments and documentation unless the existing information is obsolete or incorrect.
  - Do not alter phpdoc blocks for variables or methods unless the variables/methods themselves have changed.
  - Do not remove UML diagrams or other architectural documentation unless they are no longer accurate or relevant.
  - Always update or annotate documentation to reflect code movement or deprecation, but preserve historical context where possible.

- **Error Reporting & Logging:**
  - Use a centralized logger with configurable levels (error, warning, info, debug).
  - Logging configuration should be settable via config file or database.
  - Log all errors, exceptions, and important events.
- **Configuration Management:**
  - Store configuration in a file (e.g., config.php, .env) or a MySQL DB table.
  - Support environment-specific overrides (dev, staging, prod).
- **CI/CD Setup:**
  - Include CI/CD configuration (e.g., GitHub Actions, GitLab CI) in the repo to run tests and deploy on push.
- **Security:**
  - Sanitize and validate all user input.
  - Use prepared statements for DB access.
  - Apply least privilege to all roles and services.

- **Class Design:**
  - One class = one responsibility.
  - Limit the number of methods and properties.
- **Function Design:**
  - Functions should do one thing and do it well.
  - Prefer pure functions where possible.
- **Testing:**
  - Write unit tests for all logic.
  - Use dependency injection to facilitate mocking and testing.
- **MVC in Plugins:**
  - For WordPress/FrontAccounting, keep controllers thin, models reusable, and views simple.
- **Business Logic:**
  - Place business rules in service classes, not in controllers or views.

## 5. Example Structure

```
/your-app
  /src
    /Core        # Generic, reusable code (Composer package candidates)
    /App         # App-specific business logic
    /UI          # User interface components
  /plugins       # WordPress/FA plugins (MVC structure)
  /tests         # Unit and integration tests
```

## 6. References
- Martin Fowler, Refactoring & Patterns
- SOLID Principles (Uncle Bob)
- DRY, KISS, YAGNI
- Composer documentation

---

*This document is a living reference. Update as new best practices and patterns emerge across projects.*
