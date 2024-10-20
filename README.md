# Unkey Symfony Template

This Symfony template demonstrates how to integrate Unkey to protect API routes. Unkey enables easy API key management and route protection.

## Features
- **Create API Keys**: Generate API keys for your users.
- **Get API Key Information**: Retrieve information for a specific API key.
- **Protected Routes**: Restrict access to certain routes using Unkey API key verification.

## Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- Symfony CLI

### Clone the repository

```bash
git clone https://github.com/utkarshml/unkey_symfony.git
cd unkey-symfony
```

### Install dependencies

```bash
composer install
```

### **Set up environment variables**

In your `.env` file, set the necessary Unkey API credentials:

```env
UNKEY_API_KEY=your_unkey_api_key
UNKEY_BASE_URL=https://api.unkey.com
```
Replace your_unkey_api_key with your actual Unkey API key.

