export function singularizeAndFormat(modelName) {
    return modelName
        .replace(/-/g, " ") // Replace hyphens with spaces first
        .replace(/ies$/, "y") // Convert 'ies' to 'y' (e.g., Companies -> Company)
        .replace(/s$/, "") // Remove trailing 's' for other plural forms (e.g., Users -> User)
        .split(" ") // Split into words
        .map(
            (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ) // Capitalize each word
        .join(" "); // Join back as space-separated string
}

export function pluralizeAndFormat(modelName) {
    return modelName
        .replace(/-/g, " ") // Replace hyphens with spaces
        .split(" ") // Split words
        .map(
            (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ) // Capitalize each word
        .join(" ") // Join words back
        .replace(/\b(\w+)\b$/, (match) => {
            // Pluralize the last word
            if (match.endsWith('y')) {
                return match.slice(0, -1) + 'ies'; // Company -> Companies
            } else {
                return match + 's'; // User -> Users
            }
        });
}

export function formatName(modelName) {
    return modelName
        .replace(/-/g, " ") // Replace hyphens with spaces
        .split(" ") // Split into words
        .map(
            (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ) // Capitalize each word
        .join(" "); // Join words back with spaces
}

export function formatDate(format, dateString) {
    const date = new Date(dateString);

    const map = {
        Y: date.getFullYear(),                         // Full year (2025)
        y: String(date.getFullYear()).slice(-2),        // Last two digits of year (25)
        m: String(date.getMonth() + 1).padStart(2, "0"), // Month (01–12)
        M: new Intl.DateTimeFormat('en', { month: 'short' }).format(date), // Short month name (Jan, Feb)
        d: String(date.getDate()).padStart(2, "0"),     // Day (01–31)
    };

    return format.split('').map(char => map[char] ?? char).join('');
}

export function formatNumber(number, { style = 'decimal', currency = 'USD', minimumFractionDigits, maximumFractionDigits } = {}) {
    const options = { style };

    if (style === 'currency') {
        options.currency = currency;
    } else {
        options.minimumFractionDigits = minimumFractionDigits ?? 0;
        options.maximumFractionDigits = maximumFractionDigits ?? 2;
    }

    return new Intl.NumberFormat('en-US', options).format(number);
}

export function getStatusPillClass(status) {
    const normalized = String(status).toLowerCase(); // Normalize

    const statusMap = {
        draft: "bg-gray-100",
        pending: "bg-yellow-100",
        'partially-approved': "bg-blue-100",
        'fully-paid': "bg-green-100",
        'fully-received': "bg-green-100",
        approved: "bg-green-100",
        rejected: "bg-red-100",
        ordered: "bg-indigo-100",
        received: "bg-green-100",
        cancelled: "bg-red-100",
        'in-warehouse': "bg-green-100"
    };

    // Simple badge style with just background color and rounded corners
    return `inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${statusMap[normalized] || "bg-gray-100"}`;
}

export function humanReadable(input) {
    return String(input)
        .replace(/_/g, ' ')        // convert snake_case to space
        .replace(/-/g, ' ')        // convert kebab-case to space
        .replace(/\b\w/g, (char) => char.toUpperCase()); // capitalize each word
}

export function stripQuotes(value) {
    return value && value.startsWith('"') && value.endsWith('"')
        ? value.slice(1, -1)
        : value;
}

export function getAppName(appSettings) {
    return appSettings.name ? stripQuotes(appSettings.name) : "The EO";
}

export function getAppIcon(appSettings) {
    const iconPayload = appSettings.icon ? stripQuotes(appSettings.icon) : null;
    return iconPayload && iconPayload !== "null"
        ? `/storage/${iconPayload}`
        : "/app-settings/app-icon.png";
}

export function getAppLogo(appSettings) {
    const logoPayload = appSettings.logo ? stripQuotes(appSettings.logo) : null;
    return logoPayload && logoPayload !== "null"
        ? `/storage/${logoPayload}`
        : "/app-settings/app-logo.png";
}

