# Booking API Platform

A RESTful booking API built with Laravel and PHP.

The project is a learning and portfolio project focused on backend development, API design, authentication, authorization, database relationships, testing, Docker and DevOps.

## Tech Stack

* **PHP 8.4**
* **Laravel 13**
* **MySQL 8.4**
* **Laravel Sanctum** — API authentication
* **Eloquent ORM** — database relationships and queries
* **Docker / Docker Compose**
* **Nginx**
* **phpMyAdmin**
* **PHPUnit / Laravel Testing**

## Project Goals

The goal of this project is to build a realistic booking backend while learning and applying:

* REST API development
* Laravel architecture
* Authentication with Sanctum
* Role-based authorization
* Policies and middleware
* Form Requests and validation
* Eloquent relationships
* Database migrations and constraints
* Transactions and concurrency handling
* Booking availability
* Prevention of double bookings
* Automated testing
* Dockerized development environment
* DevOps practices

## Features

### Authentication

The API supports:

* User registration
* User login
* Authenticated user information
* Token-based authentication using Laravel Sanctum

### User Roles

The application currently supports three roles:

* `admin`
* `provider`
* `customer`

Provider and customer functionality is implemented with role-based authorization.

The user's role cannot be changed through normal registration because the role field is intentionally not mass assignable.

### Services

Providers can manage their services.

Each service contains:

* Name
* Price
* Duration
* Provider
* Created/updated timestamps

Prices are stored as integers representing the smallest currency unit.

For example:

```text
€50.00 → 5000
```

Providers can:

* Create services
* List their services
* View a service
* Update a service
* Delete a service

### Business Hours

Providers can define their working hours.

Each business hour contains:

* Day of week
* Start time
* End time
* Optional break start
* Optional break end

The database prevents a provider from creating multiple business-hour records for the same day.

Validation also ensures:

* Start time is before end time
* Break start and break end must be provided together
* Break must be before its end
* Break must be inside working hours

### Booking System

Customers can create bookings for available services.

A booking contains:

* Customer
* Service
* Start time
* Status
* Created/updated timestamps

Booking statuses:

```text
pending
confirmed
cancelled
completed
```

The intended status flow is:

```text
pending
   ├── confirmed
   │      ├── completed
   │      └── cancelled
   │
   └── cancelled
```

Invalid status transitions are rejected.

### Booking Availability

The API calculates available booking slots based on:

* Provider business hours
* Service duration
* Business breaks
* Existing pending bookings
* Existing confirmed bookings

Example:

```http
GET /api/services/{service}/availability?date=2026-10-05
```

Example response:

```json
{
    "service": {
        "id": 28,
        "name": "Oil Change",
        "duration": 60
    },
    "date": "2026-10-05",
    "available_slots": [
        "08:00",
        "10:00",
        "11:00",
        "13:00",
        "14:00",
        "15:00"
    ]
}
```

### Double Booking Prevention

Booking creation and updates use database transactions and row locking.

Before creating or updating a booking, the application checks:

1. Whether the selected time is within business hours.
2. Whether the selected time overlaps a break.
3. Whether another pending or confirmed booking overlaps the requested time.
4. Whether the service is locked during the transaction.

If the selected time is unavailable, the API returns:

```http
409 Conflict
```

Example:

```json
{
    "message": "The selected time is already booked."
}
```

## Authorization

Authorization is implemented using Laravel Policies and middleware.

Examples:

### Customers

Customers can:

* Create their own bookings
* View their own bookings
* Update their own bookings
* Cancel their own bookings

### Providers

Providers can:

* Manage their own services
* Manage their own business hours
* View bookings belonging to their services
* Confirm bookings
* Complete bookings

Users cannot access resources belonging to another provider or customer.

## API Endpoints

### Public

| Method | Endpoint        | Description     |
| ------ | --------------- | --------------- |
| GET    | `/api/hello`    | Test endpoint   |
| POST   | `/api/register` | Register a user |
| POST   | `/api/login`    | Login           |

### Authenticated

| Method | Endpoint                               | Description                |
| ------ | -------------------------------------- | -------------------------- |
| GET    | `/api/me`                              | Current authenticated user |
| GET    | `/api/bookings`                        | List user's bookings       |
| GET    | `/api/bookings/{id}`                   | Show booking               |
| POST   | `/api/bookings`                        | Create booking             |
| PUT    | `/api/bookings/{id}`                   | Update booking             |
| DELETE | `/api/bookings/{id}`                   | Cancel booking             |
| GET    | `/api/services/{service}/availability` | Get available slots        |

### Provider

| Method | Endpoint                      | Description            |
| ------ | ----------------------------- | ---------------------- |
| POST   | `/api/services`               | Create service         |
| GET    | `/api/services`               | List provider services |
| GET    | `/api/services/{id}`          | Show service           |
| PUT    | `/api/services/{id}`          | Update service         |
| DELETE | `/api/services/{id}`          | Delete service         |
| GET    | `/api/business-hours`         | List business hours    |
| POST   | `/api/business-hours`         | Create business hours  |
| PUT    | `/api/business-hours/{id}`    | Update business hours  |
| DELETE | `/api/business-hours/{id}`    | Delete business hours  |
| PATCH  | `/api/bookings/{id}/confirm`  | Confirm booking        |
| PATCH  | `/api/bookings/{id}/complete` | Complete booking       |

## Database Structure

### Users

```text
users
├── id
├── name
├── email
├── password
├── role
└── timestamps
```

### Services

```text
services
├── id
├── user_id
├── name
├── price
├── duration
└── timestamps
```

### Business Hours

```text
business_hours
├── id
├── user_id
├── day_of_week
├── start_time
├── end_time
├── break_start
├── break_end
└── timestamps
```

A unique constraint exists on:

```text
user_id + day_of_week
```

### Bookings

```text
bookings
├── id
├── user_id
├── service_id
├── start_at
├── status
└── timestamps
```

## Eloquent Relationships

The main relationships are:

```text
User
 ├── hasMany Services
 ├── hasMany Bookings
 └── hasMany BusinessHours

Service
 ├── belongsTo User
 └── hasMany Bookings

Booking
 ├── belongsTo User
 └── belongsTo Service

BusinessHour
 └── belongsTo User
```

## Project Structure

Important application directories:

```text
backend/
├── app/
│   ├── Enums/
│   │   └── BookingStatus.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   │
│   ├── Models/
│   ├── Policies/
│   └── Services/
│       └── AvailabilityService.php
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── routes/
│   └── api.php
│
├── tests/
│   └── Feature/
│
├── Dockerfile
└── ...
```

## Docker Environment

The application runs using Docker Compose.

Main containers/services:

```text
┌─────────────────────┐
│       Nginx         │
│      :8000          │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│   Laravel / PHP-FPM │
│       :9000         │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│       MySQL 8.4     │
│       :3306         │
└─────────────────────┘

┌─────────────────────┐
│     phpMyAdmin      │
│       :8080         │
└─────────────────────┘
```

Inside the Docker network, Laravel connects to MySQL using:

```env
DB_HOST=mysql
DB_PORT=3306
```

## Running the Project

### Requirements

* Docker Desktop
* Docker Compose

### Start the application

From the project directory:

```powershell
docker compose up -d
```

Check running containers:

```powershell
docker compose ps
```

### Run Laravel commands

Artisan commands are executed inside the Laravel container:

```powershell
docker compose exec app php artisan
```

Example:

```powershell
docker compose exec app php artisan migrate
```

### Run migrations and seeders

```powershell
docker compose exec app php artisan migrate:fresh --seed
```

### Clear Laravel caches

```powershell
docker compose exec app php artisan optimize:clear
```

## Access

API:

```text
http://localhost:8000
```

phpMyAdmin:

```text
http://localhost:8080
```

## Testing

The project contains feature tests covering the main API functionality.

Run the complete test suite:

```powershell
docker compose exec app php artisan test
```

Run a specific test:

```powershell
docker compose exec app php artisan test --filter=BookingTest
```

The tests cover areas such as:

* Authentication
* Services
* Business hours
* Bookings
* Authorization
* Booking status transitions
* Availability
* Double-booking prevention
* Validation

## Development Approach

The project is being developed incrementally:

```text
Learn
  ↓
Implement
  ↓
Test
  ↓
Break something
  ↓
Debug
  ↓
Understand the problem
  ↓
Improve the implementation
```

The focus is not only on making the API work, but also on understanding why each part exists and how the individual components interact.

## Current Development Status

Implemented:

* Laravel API
* Docker environment
* MySQL
* Nginx
* phpMyAdmin
* Sanctum authentication
* User roles
* Role middleware
* Policies
* Services
* Business hours
* Bookings
* Booking status enum
* Booking availability
* Double-booking checks
* Form Request validation
* API Resources
* Database relationships
* Database constraints
* Automated tests
* Seeders

Planned DevOps work:

* Production Docker configuration
* Kubernetes
* CI/CD
* Infrastructure as Code with Terraform
* Cloud deployment
* Production monitoring

## Why This Project?

This project is designed to demonstrate practical backend and DevOps skills through a realistic use case rather than isolated tutorials.

The booking domain provides real problems to solve, including:

* Authentication
* Authorization
* Ownership
* Time-based availability
* Database relationships
* Transactions
* Concurrency
* Validation
* State transitions
* Automated testing
* Containerization
* Deployment
* Infrastructure management
