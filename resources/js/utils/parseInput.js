export function parseInput(fields, formData) {
    const formDataObj = new FormData();

    fields.forEach((field) => {
        const fieldValue = formData[field.model];

        if (field.type === "file") {
            if (fieldValue instanceof File) {
                formDataObj.append(field.model, fieldValue); // Add file if valid
            }
        } else {
            formDataObj.append(field.model, fieldValue || ""); // Add other fields
        }
    });

    return formDataObj;
}