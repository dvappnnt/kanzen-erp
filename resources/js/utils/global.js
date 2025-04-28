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
