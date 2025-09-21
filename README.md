# USISA-PH: Philippine Government Projects Tracker

> **Usisa** (oo-SEE-sah) - Filipino word meaning "to inquire" or "to investigate"

A community-driven, open-source platform for tracking and monitoring Philippine government projects with radical transparency and accountability.

## Mission

To boldly code where no taxpayer has gone before — showcasing full stack sorcery while delivering a clear, no-fluff, crowd-powered tracker of government projects. Because let's face it, if we're paying taxes, we deserve a seat at the table (or at least a public dashboard) showing where every peso went. No spin, no political mumbo jumbo, just cold, hard facts and a little bit of developer humor to keep things sane. Together, we turn noise into knowledge, and confusion into community-powered clarity — proving that transparency is not just a buzzword, but a tech superhero's code.

## Vision

To build a thriving digital citizen watchdog fueled by curiosity and caffeine, where Filipino families can finally see their taxes at work in health, food, flood control, education, and yes, rooting out corruption — all in real time. A future where government projects aren't just buried in a pile of papers but live in a public, trustable repository — updated by everyday heroes (contributors) committed to truth and justice, armed with fact-checking superpowers. It's transparency with teeth, where data drives action, and the Philippines finally shakes off the deep debt blues with community courage and code.

## Key Features

- **Real-time Project Tracking**: Monitor government projects across various sectors
- **Multi-source Data Integration**: Aggregates data from DIME and other official sources
- **Community Fact-Checking**: Crowd-sourced verification and validation system
- **Sector-based Filtering**: Focus on health, education, flood control, food security, and anti-corruption
- **Historical Analysis**: Track project timelines, budget changes, and completion rates
- **Public API**: Open access to all project data for developers and researchers

## How We Achieve Transparency

### 1. Community Contributions with Fact-Check Workflow
- Every data entry or update goes through pull requests
- Requires at least two independent fact-checkers to approve changes
- Public changelog documents all corrections and updates

### 2. Open Data Scraping with Source Transparency
- All data sources are clearly documented
- Raw scraped data published alongside processed data for auditing
- Automated scrapers for DIME and other government portals

### 3. Reputation and Accountability System
- Contributor badges based on reliability and accuracy
- Dispute resolution process mediated by senior contributors
- Track record of all contributor actions

### 4. Automation for Data Validation
- Automated scripts cross-verify data with official records
- Regular consistency checks and anomaly detection
- Alert system for suspicious data changes

### 5. No Political Agenda Enforcement
- Strict ban on political endorsements and biased edits
- Facts-only policy with required source citations
- Swift moderation of potentially biased content

### 6. User-Friendly Transparency Dashboard
- Visual progress indicators for all projects
- Sector-specific views and filters
- Budget allocation and utilization tracking

## Tech Stack

- **Backend**: Laravel 11 (PHP)
- **Database**: MySQL/PostgreSQL
- **Frontend**: Blade Templates / Livewire
- **Data Sources**: DIME, Government APIs, Public Records
- **Scraping**: Custom scrapers with source attribution
- **Caching**: Redis
- **Queue**: Laravel Queue for background processing

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0+ or PostgreSQL 13+
- Node.js 18+ and NPM
- Git

### Installation

1. Clone the repository
```bash
git clone https://github.com/iamgerwin/usisa-ph-laravel.git
cd usisa-ph-laravel
```

2. Install PHP dependencies
```bash
composer install
```

3. Install NPM dependencies
```bash
npm install
```

4. Copy environment file and configure
```bash
cp .env.example .env
php artisan key:generate
```

5. Configure your database in `.env` file
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=usisa_ph
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run database migrations
```bash
php artisan migrate
```

7. Seed initial data (if available)
```bash
php artisan db:seed
```

8. Build frontend assets
```bash
npm run build
```

9. Start the development server
```bash
php artisan serve
```

Visit `http://localhost:8000` to see the application.

## Contributing

We welcome contributions from developers, data analysts, fact-checkers, and concerned citizens! Please read our [Contributing Guidelines](CONTRIBUTING.md) before submitting pull requests.

### How to Contribute

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes with clear, descriptive messages
4. Push to your branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request with detailed description
6. Wait for review from at least two maintainers

### Contribution Guidelines

- **Data Accuracy**: All data must be verifiable with official sources
- **Code Quality**: Follow PSR-12 coding standards for PHP
- **Testing**: Include tests for new features
- **Documentation**: Update documentation for any API changes
- **Fact-Checking**: Provide sources for all data entries
- **No Political Bias**: Keep contributions neutral and fact-based

## Data Sources

Current data sources include:
- **DIME (Data in Motion Explorer)**: Primary source for government project data
- **PSA PSGC**: Philippine Standard Geographic Code for locations hierarchy
- **DBM (Department of Budget and Management)**: Budget allocation data
- **DPWH**: Infrastructure project updates
- **DepEd**: Education project information
- **DOH**: Health program tracking

All scraped data includes source attribution and timestamp.

### PSGC Data Scraper

The Philippine Standard Geographic Code (PSGC) scraper maintains up-to-date geographic hierarchy data from the Philippine Statistics Authority.

#### Features
- Automated scraping of regions, provinces, cities, municipalities, and barangays
- Maintains proper hierarchical relationships using 10-digit PSGC codes
- Tracks income classifications and urban/rural designations
- Granular error handling with resumable scraping jobs
- Source attribution and sync timestamp tracking

#### Usage

Check current PSGC data status:
```bash
php artisan psgc:status
php artisan psgc:status --detailed  # Show scraper job statistics
```

Run PSGC scrapers:
```bash
# Scrape all geographic levels (in proper order)
php artisan psgc:scrape

# Scrape specific geographic level
php artisan psgc:scrape regions
php artisan psgc:scrape provinces
php artisan psgc:scrape cities
php artisan psgc:scrape municipalities
php artisan psgc:scrape barangays

# Run scraper in background queue
php artisan psgc:scrape --queue

# Force scraping even if another job is running
php artisan psgc:scrape --force
```

#### Data Structure
- **Regions**: Top-level geographic divisions with PSA codes
- **Provinces**: Under regions, includes income classification
- **Cities**: Independent or under provinces, includes city class and income classification
- **Municipalities**: Under provinces, includes income classification
- **Barangays**: Smallest unit under cities/municipalities, includes urban/rural classification

The scraper maintains both original codes and PSA PSGC codes for backward compatibility while ensuring data integrity through the official PSGC system.

## API Documentation

Our API provides programmatic access to all project data:

```
GET /api/projects - List all projects
GET /api/projects/{id} - Get specific project details
GET /api/sectors - List all sectors
GET /api/sectors/{sector}/projects - Get projects by sector
GET /api/sources - List all data sources
```

Full API documentation available at `/api/documentation` when running locally.

## Security and Privacy

- No personal data collection beyond public official information
- All contributions are public and transparent
- Regular security audits and dependency updates
- Responsible disclosure policy for vulnerabilities

## License

This project is open-sourced software licensed under the [MIT License](LICENSE).

## Support

For questions, suggestions, or issues:
- Open an issue on GitHub
- Join our community Discord server
- Email: support@usisa-ph.org (coming soon)

## Acknowledgments

- Filipino taxpayers who deserve transparency
- Open-source community for amazing tools
- Contributors who volunteer their time for truth
- Government agencies providing public data
- Coffee shops with good WiFi and stronger coffee

---

**Remember**: This is more than code — it's a movement for transparency, accountability, and citizen empowerment. Every line of code, every fact-check, every contribution brings us closer to a Philippines where public funds truly serve the public good.

**Mabuhay ang Transparency! Long Live Open Data!**