# PROJECT GENERATION PROMPT DOCUMENT

## Campus Event Management Platform (College-Specific)

---

## 1. PROJECT OVERVIEW

Design and develop a **multi-role web-based Campus Event Management Platform** tailored for a single college ecosystem.

The system must enforce **institutional authenticity**, **role-based workflows**, and **controlled event publishing** while delivering a **premium, minimalistic, and modern UI/UX**.

---

## 2. CORE OBJECTIVE

Build a scalable, secure, and user-friendly platform that enables:

- Centralized event management within a college
- Controlled organizer and event approvals
- Seamless student participation and volunteer management
- Digital ticketing and attendance tracking

---

## 3. USER ROLES & ACCESS CONTROL

### 3.1 Student

- Register using **college-issued email ID only**
- Login and manage profile
- Browse and filter events
- Register as:
  - Participant
  - Volunteer (if enabled)

- View **event tickets/passcards**
- Access **active registrations dashboard**

---

### 3.2 Event Organizer

- Register and request verification
- Must be **approved by Admin**
- Create events with:
  - Title, description, category
  - Date, time, venue
  - Approval document upload (mandatory)
  - Volunteer requirement toggle

- Manage:
  - Volunteer approvals
  - Participant list
  - Attendance marking

- Export attendance data (CSV)

---

### 3.3 Admin

- Approve/reject:
  - Organizer registrations
  - Event submissions

- Monitor platform activity
- Maintain system integrity

---

## 4. KEY SYSTEM FEATURES

### 4.1 Authentication & Validation

- Email domain restriction (college domain only)
- Secure login/session management
- Role-based access control

---

### 4.2 Event Lifecycle

1. Organizer creates event
2. Uploads approval document
3. Admin reviews event
4. Approved → Event published
5. Students register
6. Event occurs
7. Organizer marks attendance

---

### 4.3 Volunteer System

- Organizer enables volunteer requirement
- Students apply
- Organizer approves/rejects volunteers

---

### 4.4 Ticketing System

- Generate **unique registration ID**
- Create **digital passcard**
- Passcard visible in student dashboard
- Used for entry validation

---

### 4.5 Attendance Management

- Organizer dashboard shows participants
- Mark attendance manually
- Export attendance (CSV format)

---

### 4.6 Public Event Discovery

- Home page shows all approved events
- Categorized display
- Search and filter support

---

## 5. TECH STACK REQUIREMENTS

### Frontend

- HTML5
- CSS3 (prefer modern layout: Flexbox/Grid)
- JavaScript (Vanilla or modular)

### Backend

- PHP (structured, modular, MVC-like approach preferred)

### Database

- MySQL (normalized relational schema)

---

## 6. DATABASE DESIGN

_-- USERS Table [cite: 2]_

CREATE TABLE users (

    user_id **INT** AUTO_INCREMENT **PRIMARY KEY**, *-- [cite: 3]*

name **VARCHAR**(100) NOT NULL, _-- [cite: 3]_

    email **VARCHAR**(100) UNIQUE NOT NULL, *-- [cite: 3]*

password **VARCHAR**(255) NOT NULL, _-- [cite: 3]_

role ENUM('Student', 'Organizer', 'Administrator') NOT NULL, _-- [cite: 3]_

    is_verified **BOOLEAN** **DEFAULT** FALSE, *-- [cite: 3]*

    phone **VARCHAR**(15), *-- [cite: 3]*

year **INT**, _-- [cite: 3]_

    department **VARCHAR**(100), *-- [cite: 3]*

    admission_number **VARCHAR**(50) *-- [cite: 3]*

);

\

_-- CATEGORIES Table _

CREATE TABLE categories (

    category_id **INT** AUTO_INCREMENT **PRIMARY KEY**,

name **VARCHAR**(100) NOT NULL

);

\

_-- EVENTS Table [cite: 5]_

CREATE TABLE events (

    event_id **INT** AUTO_INCREMENT **PRIMARY KEY**, *-- [cite: 6]*

    title **VARCHAR**(200) NOT NULL, *-- [cite: 6]*

    category_id **INT**(50) NOT NULL,

    approval_doc_path **VARCHAR**(255), *-- [cite: 6]*

    is_published **BOOLEAN** **DEFAULT** FALSE, *-- [cite: 6]*

    event_date **DATE**, *-- [cite: 6]*

    start_time **TIME**, *-- [cite: 6]*

    end_time **TIME**, *-- [cite: 6]*

    application_deadline DATETIME, *-- [cite: 6]*

    venue **VARCHAR**(100), *-- [cite: 6]*

    place **VARCHAR**(100), *-- [cite: 6]*

    total_slots **INT**, *-- [cite: 6]*

    slots_left **INT**, *-- [cite: 6]*

description **TEXT**, _-- [cite: 6]_

    created_at **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP, *-- [cite: 6]*

    created_by **INT**, *-- [cite: 6]*

    organizer **VARCHAR**(100), *-- [cite: 6]*

    poc_id **INT**, *-- [cite: 6]*

    is_volunteer_required **BOOLEAN** **DEFAULT** FALSE, *-- [cite: 6]*

    participant_whatsapp_link **VARCHAR**(255), *-- [cite: 6]*

    volunteer_whatsapp_link **VARCHAR**(255), *-- [cite: 6]*

**FOREIGN KEY** (created_by) **REFERENCES** users(user_id),

**FOREIGN KEY** (category_id) **REFERENCES** categories(category_id)

\

);

\

_-- REGISTRATIONS Table [cite: 7]_

CREATE TABLE registrations (

    reg_id **INT** AUTO_INCREMENT **PRIMARY KEY**, *-- [cite: 8]*

    user_id **INT**, *-- [cite: 8]*

    event_id **INT**, *-- [cite: 8]*

type ENUM('Participant', 'Volunteer') NOT NULL, _-- [cite: 8]_

    vol_approval_status ENUM('Pending', 'Approved', 'Rejected') **DEFAULT** 'Pending', *-- [cite: 8]*

    attendance_marked **BOOLEAN** **DEFAULT** FALSE, *-- [cite: 8]*

**FOREIGN KEY** (user_id) **REFERENCES** users(user_id),

**FOREIGN KEY** (event_id) **REFERENCES** events(event_id)

);

Ensure:

- Proper foreign key relationships
- Indexing for performance
- Data integrity constraints

---

## 7. UI/UX DESIGN DIRECTIVES

### 7.1 Design Philosophy

- Minimalistic
- Premium feel
- Clean typography
- High readability
- Consistent spacing and alignment

---

### 7.2 Visual Style

- Soft color palette (light backgrounds, subtle accents)
- Card-based layouts
- Smooth shadows and depth
- Rounded components (modern aesthetic)

---

### 7.3 Animations & Interactions

- Smooth transitions (CSS animations)
- Micro-interactions:
  - Button hover effects
  - Card elevation on hover
  - Form input focus animations

- Page transitions should feel fluid, not abrupt

---

### 7.4 UX Principles

- Zero confusion navigation
- Clear call-to-action buttons
- Minimal clicks for core actions
- Responsive design (mobile + desktop)

---

## 8. SYSTEM ARCHITECTURE

Follow a **modular layered architecture**:

- Presentation Layer (UI)
- Application Layer (PHP logic)
- Data Layer (MySQL)

Ensure:

- Separation of concerns
- Reusable components
- Clean routing structure

---

## 9. SECURITY REQUIREMENTS

- Input validation (client + server side)
- SQL injection prevention (prepared statements)
- Session security
- File upload validation (for approval documents)

---

## 10. DELIVERABLES EXPECTED FROM AI AGENT

- Fully functional web application
- Clean folder structure
- Well-documented code
- SQL schema with sample data
- Responsive UI implementation
- Error handling mechanisms

---

## 11. FUTURE SCALABILITY CONSIDERATIONS

- Multi-college support (tenant-based architecture)
- API-first design for mobile apps
- Notification system (email/SMS)
- QR-based attendance
- Payment gateway integration

---

## 12. SUCCESS CRITERIA

The system is considered successful if:

- All roles function independently without conflict
- Event approval workflow is enforced strictly
- UI is clean, responsive, and modern
- No critical security or data integrity issues
- Codebase is modular and maintainable

---

## 13. INSTRUCTION TO AI AGENT

Generate the project in a **step-by-step structured manner**, ensuring:

- No missing dependencies
- No broken workflows
- No inconsistent naming conventions
- Maintain clean, readable, production-level code

Avoid shortcuts. Prioritize clarity, structure, and maintainability over speed.

---
