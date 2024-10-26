// Initial setup
const formSettings = window.carShippingFormSettings || {};
const { useState, useEffect, useRef } = React;

// Progress Bar Component
const ProgressBar = ({ currentStep }) => {
    const steps = ['Shipping Details', 'Vehicle Information', 'Contact Information'];
    
    return React.createElement(
        'div',
        { className: 'progress-bar' },
        steps.map((step, index) =>
            React.createElement(
                'div',
                {
                    key: index,
                    className: `progress-step ${index + 1 === currentStep ? 'active' : ''} 
                               ${index + 1 < currentStep ? 'completed' : ''}`
                },
                step
            )
        )
    );
};
// Debug Utility
const DEBUG = true;

const log = (message, data = null) => {
    if (DEBUG) {
        if (data) {
            console.log(`[Car Shipping Form] ${message}:`, data);
        } else {
            console.log(`[Car Shipping Form] ${message}`);
        }
    }
};
// Phone Number Formatter
const formatPhoneNumber = (value) => {
    // Remove all non-digit characters
    const numbers = value.replace(/\D/g, '');
    
    // Return empty string if no numbers
    if (numbers.length === 0) return '';
    
    // Handle partial inputs
    if (numbers.length <= 3) {
        return numbers;
    }
    if (numbers.length <= 6) {
        return `(${numbers.slice(0, 3)}) ${numbers.slice(3)}`;
    }
    
    // Return formatted 10-digit number
    return `(${numbers.slice(0, 3)}) ${numbers.slice(3, 6)}-${numbers.slice(6, 10)}`;
};

    // Step 1 Component
const Step1 = ({ nextStep, formData, setFormData }) => {
    const [errors, setErrors] = useState({});
    const [isLoading, setIsLoading] = useState(true);
    const pickupAutocompleteRef = useRef(null);
    const dropoffAutocompleteRef = useRef(null);

    const fieldLabels = {
        pickupLocation: 'Pickup Location',
        dropoffLocation: 'Delivery Location',
        transportType: 'Transport Type'
    };

    useEffect(() => {
        let timeoutId;

        const initializeAutocomplete = () => {
            try {
                const options = {
                    componentRestrictions: { country: ['us', 'ca'] },
                    fields: ['address_components', 'geometry'],
                    // For ZIP code search, we need to use 'geocode' type
                    types: []  // Empty array allows all types
                };

                // Initialize pickup autocomplete
                const pickupInput = document.getElementById('pickup-location');
                if (pickupInput) {
                    const pickupAutocomplete = new window.google.maps.places.Autocomplete(
                        pickupInput,
                        options
                    );
                    
                    // Set search fields to prioritize postal codes and localities
                    pickupAutocomplete.setFields(['address_components']);
                    
                    pickupAutocomplete.addListener('place_changed', () => {
                        const place = pickupAutocomplete.getPlace();
                        if (place && place.address_components) {
                            let city = '';
                            let state = '';
                            let zipCode = '';
                            
                            place.address_components.forEach(component => {
                                if (component.types.includes('locality')) {
                                    city = component.long_name;
                                } else if (component.types.includes('sublocality_level_1')) {
                                    // Fallback for some cities
                                    city = city || component.long_name;
                                }
                                if (component.types.includes('administrative_area_level_1')) {
                                    state = component.short_name;
                                }
                                if (component.types.includes('postal_code')) {
                                    zipCode = component.long_name;
                                }
                            });

                            // Format the location string based on available components
                            let locationString = '';
                            if (zipCode && city && state) {
                                locationString = `${city}, ${state} ${zipCode}`;
                            } else if (city && state) {
                                locationString = `${city}, ${state}`;
                            } else if (zipCode) {
                                locationString = zipCode;
                            }

                            setFormData(prev => ({
                                ...prev,
                                pickupLocation: locationString
                            }));
                            setErrors(prev => ({ ...prev, pickupLocation: '' }));
                        }
                    });
                    pickupAutocompleteRef.current = pickupAutocomplete;
                }

                // Initialize dropoff autocomplete with the same configuration
                const dropoffInput = document.getElementById('dropoff-location');
                if (dropoffInput) {
                    const dropoffAutocomplete = new window.google.maps.places.Autocomplete(
                        dropoffInput,
                        options
                    );
                    
                    // Set search fields to prioritize postal codes and localities
                    dropoffAutocomplete.setFields(['address_components']);
                    
                    dropoffAutocomplete.addListener('place_changed', () => {
                        const place = dropoffAutocomplete.getPlace();
                        if (place && place.address_components) {
                            let city = '';
                            let state = '';
                            let zipCode = '';
                            
                            place.address_components.forEach(component => {
                                if (component.types.includes('locality')) {
                                    city = component.long_name;
                                } else if (component.types.includes('sublocality_level_1')) {
                                    // Fallback for some cities
                                    city = city || component.long_name;
                                }
                                if (component.types.includes('administrative_area_level_1')) {
                                    state = component.short_name;
                                }
                                if (component.types.includes('postal_code')) {
                                    zipCode = component.long_name;
                                }
                            });

                            // Format the location string based on available components
                            let locationString = '';
                            if (zipCode && city && state) {
                                locationString = `${city}, ${state} ${zipCode}`;
                            } else if (city && state) {
                                locationString = `${city}, ${state}`;
                            } else if (zipCode) {
                                locationString = zipCode;
                            }

                            setFormData(prev => ({
                                ...prev,
                                dropoffLocation: locationString
                            }));
                            setErrors(prev => ({ ...prev, dropoffLocation: '' }));
                        }
                    });
                    dropoffAutocompleteRef.current = dropoffAutocomplete;
                }

                setIsLoading(false);
            } catch (error) {
                console.error('Error initializing autocomplete:', error);
                setIsLoading(false);
            }
        };

        // Check for Google Maps
        if (window.google && window.google.maps && window.google.maps.places) {
            initializeAutocomplete();
        } else {
            timeoutId = setTimeout(initializeAutocomplete, 1000);
        }

        return () => {
            if (timeoutId) clearTimeout(timeoutId);
        };
    }, []);

    // Rest of the component remains the same...

    // Rest of the component remains the same...

    const handleChange = (field) => (e) => {
        const { value } = e.target;
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
        
        // Clear error when field is changed
        if (errors[field]) {
            setErrors(prev => ({
                ...prev,
                [field]: ''
            }));
        }
    };

    const validateStep = () => {
        const newErrors = {};
        
        // Check required fields
        if (!formData.pickupLocation) {
            newErrors.pickupLocation = `Please enter ${fieldLabels.pickupLocation}`;
        }
        
        if (!formData.dropoffLocation) {
            newErrors.dropoffLocation = `Please enter ${fieldLabels.dropoffLocation}`;
        }
        
        if (!formData.transportType) {
            newErrors.transportType = `Please select ${fieldLabels.transportType}`;
        }

        setErrors(newErrors);
        
        // If there are any errors, scroll to the first one
        if (Object.keys(newErrors).length > 0) {
            const firstErrorElement = document.querySelector('.error-message');
            if (firstErrorElement) {
                firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return Object.keys(newErrors).length === 0;
    };

    const handleNext = () => {
        if (validateStep()) {
            nextStep();
        }
    };

    return React.createElement(
        'div',
        { className: 'form-step' },
        React.createElement('h2', null, 'Shipping Details'),

        // Pickup Location
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Pickup Location *'),
            React.createElement('input', {
                id: 'pickup-location',
                type: 'text',
                placeholder: 'City or Zip Code',
                value: formData.pickupLocation || '',
                onChange: handleChange('pickupLocation'),
                className: `form-input ${errors.pickupLocation ? 'error' : ''}`,
                autoComplete: 'off'
            }),
            errors.pickupLocation && React.createElement(
                'span',
                { className: 'error-message' },
                errors.pickupLocation
            )
        ),

        // Dropoff Location
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Delivery Location *'),
            React.createElement('input', {
                id: 'dropoff-location',
                type: 'text',
                placeholder: 'City or Zip Code',
                value: formData.dropoffLocation || '',
                onChange: handleChange('dropoffLocation'),
                className: `form-input ${errors.dropoffLocation ? 'error' : ''}`,
                autoComplete: 'off'
            }),
            errors.dropoffLocation && React.createElement(
                'span',
                { className: 'error-message' },
                errors.dropoffLocation
            )
        ),

        // Transport Type
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Transport Type *'),
            React.createElement(
                'div',
                { className: 'radio-group' },
                React.createElement(
                    'label',
                    { className: 'radio-label' },
                    React.createElement('input', {
                        type: 'radio',
                        name: 'transportType',
                        value: 'Open',
                        checked: formData.transportType === 'Open',
                        onChange: handleChange('transportType')
                    }),
                    'Open Transport'
                ),
                React.createElement(
                    'label',
                    { className: 'radio-label' },
                    React.createElement('input', {
                        type: 'radio',
                        name: 'transportType',
                        value: 'Enclosed',
                        checked: formData.transportType === 'Enclosed',
                        onChange: handleChange('transportType')
                    }),
                    'Enclosed Transport'
                )
            ),
            errors.transportType && React.createElement(
                'span',
                { className: 'error-message' },
                errors.transportType
            )
        ),

        // Next Button
        React.createElement(
            'button',
            {
                onClick: handleNext,
                className: 'form-button'
            },
            'Next Step'
        )
    );
};

// Step2

const Step2 = ({ nextStep, prevStep, formData, setFormData }) => {
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);
    
    // Field labels for validation messages
    const fieldLabels = {
        manufacturer: 'Vehicle Make',
        model: 'Vehicle Model',
        year: 'Vehicle Year',
        isOperable: 'Vehicle Operability Status'
    };

    const manufacturers = [
        'Acura', 'Audi', 'BMW', 'Buick', 'Cadillac', 'Chevrolet', 'Chrysler', 
        'Dodge', 'Ford', 'GMC', 'Honda', 'Hyundai', 'Infiniti', 'Jaguar', 
        'Jeep', 'Kia', 'Lexus', 'Lincoln', 'Mazda', 'Mercedes-Benz', 'Mercury',
        'Mini', 'Mitsubishi', 'Nissan', 'Porsche', 'Ram', 'Subaru', 'Tesla',
        'Toyota', 'Volkswagen', 'Volvo'
    ];
    
    const modelsByManufacturer = {
        'Toyota': ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Tacoma', 'Tundra', '4Runner', 'Prius'],
        'Honda': ['Civic', 'Accord', 'CR-V', 'Pilot', 'Odyssey', 'HR-V', 'Ridgeline'],
        'Ford': ['F-150', 'Mustang', 'Explorer', 'Escape', 'Edge', 'Ranger', 'Bronco'],
        'Chevrolet': ['Silverado', 'Malibu', 'Equinox', 'Tahoe', 'Camaro', 'Traverse'],
        'Nissan': ['Altima', 'Rogue', 'Sentra', 'Maxima', 'Pathfinder', 'Frontier'],
        // Add more as needed
    };

    const getModels = (manufacturer) => {
        return modelsByManufacturer[manufacturer] || [];
    };

    const handleChange = (field) => (e) => {
        const { value } = e.target;

        if (field === 'manufacturer') {
            setFormData(prev => ({
                ...prev,
                manufacturer: value,
                model: '' // Reset model when manufacturer changes
            }));
        } else {
            setFormData(prev => ({
                ...prev,
                [field]: value
            }));
        }
        
        // Clear error when field is changed
        if (errors[field]) {
            setErrors(prev => ({
                ...prev,
                [field]: ''
            }));
        }
    };

    const validateStep = () => {
        const newErrors = {};
        const requiredFields = ['manufacturer', 'model', 'year', 'isOperable'];
        
        requiredFields.forEach(field => {
            if (!formData[field]) {
                newErrors[field] = `Please select ${fieldLabels[field]}`;
            }
        });

        // Add specific validation messages for each field
        if (formData.manufacturer && !modelsByManufacturer[formData.manufacturer]) {
            newErrors.model = 'Please select a valid model for this manufacturer';
        }

        if (formData.year) {
            const year = parseInt(formData.year);
            const currentYear = new Date().getFullYear();
            if (year < 1900 || year > currentYear + 1) {
                newErrors.year = 'Please select a valid year';
            }
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleNext = () => {
        if (validateStep()) {
            nextStep();
        } else {
            // Scroll to first error
            const firstErrorElement = document.querySelector('.error-message');
            if (firstErrorElement) {
                firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    };

    // Generate years array (current year + 1 down to 1900)
    const years = Array.from(
        { length: (new Date().getFullYear() + 1) - 1900 }, 
        (_, i) => new Date().getFullYear() + 1 - i
    );

    return React.createElement(
        'div',
        { className: 'form-step' },
        React.createElement('h2', null, 'Vehicle Information'),

        // Manufacturer
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Vehicle Make *'),
            React.createElement(
                'select',
                {
                    value: formData.manufacturer || '',
                    onChange: handleChange('manufacturer'),
                    className: `form-select ${errors.manufacturer ? 'error' : ''}`,
                },
                React.createElement('option', { value: '' }, 'Select Make'),
                manufacturers.map(manufacturer => 
                    React.createElement('option', {
                        key: manufacturer,
                        value: manufacturer
                    }, manufacturer)
                )
            ),
            errors.manufacturer && React.createElement(
                'span',
                { className: 'error-message' },
                errors.manufacturer
            )
        ),

        // Model
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Vehicle Model *'),
            React.createElement(
                'select',
                {
                    value: formData.model || '',
                    onChange: handleChange('model'),
                    className: `form-select ${errors.model ? 'error' : ''}`,
                    disabled: !formData.manufacturer
                },
                React.createElement('option', { value: '' }, 'Select Model'),
                getModels(formData.manufacturer).map(model => 
                    React.createElement('option', {
                        key: model,
                        value: model
                    }, model)
                )
            ),
            errors.model && React.createElement(
                'span',
                { className: 'error-message' },
                errors.model
            )
        ),

        // Year
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Vehicle Year *'),
            React.createElement(
                'select',
                {
                    value: formData.year || '',
                    onChange: handleChange('year'),
                    className: `form-select ${errors.year ? 'error' : ''}`,
                },
                React.createElement('option', { value: '' }, 'Select Year'),
                years.map(year => 
                    React.createElement('option', {
                        key: year,
                        value: year
                    }, year)
                )
            ),
            errors.year && React.createElement(
                'span',
                { className: 'error-message' },
                errors.year
            )
        ),

        // Is Operable
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Is the vehicle operable? *'),
            React.createElement(
                'div',
                { className: 'radio-group' },
                React.createElement(
                    'label',
                    { className: 'radio-label' },
                    React.createElement('input', {
                        type: 'radio',
                        name: 'isOperable',
                        value: 'Yes',
                        checked: formData.isOperable === 'Yes',
                        onChange: handleChange('isOperable')
                    }),
                    'Yes'
                ),
                React.createElement(
                    'label',
                    { className: 'radio-label' },
                    React.createElement('input', {
                        type: 'radio',
                        name: 'isOperable',
                        value: 'No',
                        checked: formData.isOperable === 'No',
                        onChange: handleChange('isOperable')
                    }),
                    'No'
                )
            ),
            errors.isOperable && React.createElement(
                'span',
                { className: 'error-message' },
                errors.isOperable
            )
        ),

        // Navigation Buttons
        React.createElement(
            'div',
            { className: 'button-group' },
            React.createElement(
                'button',
                {
                    onClick: prevStep,
                    className: 'form-button secondary'
                },
                'Previous Step'
            ),
            React.createElement(
                'button',
                {
                    onClick: handleNext,
                    className: 'form-button'
                },
                'Next Step'
            )
        )
    );
};

const Step3 = ({ prevStep, formData, setFormData }) => {
    const [errors, setErrors] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitStatus, setSubmitStatus] = useState(null);

    const fieldLabels = {
        name: 'Full Name',
        email: 'Email Address',
        phone: 'Phone Number',
        availabilityDate: 'Shipping Timeline'
    };

    const availabilityOptions = [
        'ASAP',
        'Within 2 weeks',
        'Within 30 days',
        'More than 30 days'
    ];

    const handleChange = (field) => (e) => {
        let { value } = e.target;

        // Format phone number as user types
        if (field === 'phone') {
            value = formatPhoneNumber(value);
        }

        setFormData(prev => ({
            ...prev,
            [field]: value
        }));

        // Clear error when field is changed
        if (errors[field]) {
            setErrors(prev => ({
                ...prev,
                [field]: ''
            }));
        }
    };

    const validateEmail = (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };

    const validatePhone = (phone) => {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length === 10;
    };

    const validateStep = () => {
        const newErrors = {};
        
        // Name validation
        if (!formData.name) {
            newErrors.name = 'Please enter your full name';
        } else if (formData.name.length < 2) {
            newErrors.name = 'Name must be at least 2 characters long';
        }

        // Email validation
        if (!formData.email) {
            newErrors.email = 'Please enter your email address';
        } else if (!validateEmail(formData.email)) {
            newErrors.email = 'Please enter a valid email address';
        }

        // Phone validation
        if (!formData.phone) {
            newErrors.phone = 'Please enter your phone number';
        } else if (!validatePhone(formData.phone)) {
            newErrors.phone = 'Please enter a valid 10-digit phone number';
        }

        // Availability validation
        if (!formData.availabilityDate) {
            newErrors.availabilityDate = 'Please select when you need the vehicle shipped';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async () => {
        if (!validateStep()) {
            // Scroll to first error
            const firstErrorElement = document.querySelector('.error-message');
            if (firstErrorElement) {
                firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        setIsSubmitting(true);
        setSubmitStatus(null);

        try {
            const formBody = new URLSearchParams({
                action: 'submit_shipping_form',
                nonce: formSettings.nonce,
                ...formData
            });

            const response = await fetch(formSettings.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formBody
            });

            const result = await response.json();

            if (result.success) {
                setSubmitStatus({
                    type: 'success',
                    message: 'Your shipping request has been submitted successfully! We will contact you shortly.'
                });

                // Optional: Reset form or redirect after success
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                throw new Error(result.data?.message || 'Submission failed');
            }
        } catch (err) {
            console.error('Submission error:', err);
            setSubmitStatus({
                type: 'error',
                message: 'An error occurred while submitting the form. Please try again.'
            });
        } finally {
            setIsSubmitting(false);
        }
    };

    // Show success message after submission
    if (submitStatus?.type === 'success') {
        return React.createElement(
            'div',
            { className: 'form-step success-message' },
            React.createElement('h2', null, 'Thank You!'),
            React.createElement('p', null, submitStatus.message)
        );
    }

    return React.createElement(
        'div',
        { className: 'form-step' },
        React.createElement('h2', null, 'Contact Information'),

        // Show error message if submission failed
        submitStatus?.type === 'error' && React.createElement(
            'div',
            { className: 'error-alert' },
            submitStatus.message
        ),

        // Name Field
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Full Name *'),
            React.createElement('input', {
                type: 'text',
                placeholder: 'Enter your full name',
                value: formData.name || '',
                onChange: handleChange('name'),
                className: `form-input ${errors.name ? 'error' : ''}`,
                disabled: isSubmitting
            }),
            errors.name && React.createElement(
                'span',
                { className: 'error-message' },
                errors.name
            )
        ),

        // Email Field
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Email Address *'),
            React.createElement('input', {
                type: 'email',
                placeholder: 'Enter your email address',
                value: formData.email || '',
                onChange: handleChange('email'),
                className: `form-input ${errors.email ? 'error' : ''}`,
                disabled: isSubmitting
            }),
            errors.email && React.createElement(
                'span',
                { className: 'error-message' },
                errors.email
            )
        ),

        // Phone Field
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'Phone Number *'),
            React.createElement('input', {
                type: 'tel',
                placeholder: '(555) 555-5555',
                value: formData.phone || '',
                onChange: handleChange('phone'),
                className: `form-input ${errors.phone ? 'error' : ''}`,
                disabled: isSubmitting
            }),
            errors.phone && React.createElement(
                'span',
                { className: 'error-message' },
                errors.phone
            )
        ),

        // Availability Date Field
        React.createElement(
            'div',
            { className: 'form-group' },
            React.createElement('label', null, 'When do you need the vehicle shipped? *'),
            React.createElement(
                'select',
                {
                    value: formData.availabilityDate || '',
                    onChange: handleChange('availabilityDate'),
                    className: `form-select ${errors.availabilityDate ? 'error' : ''}`,
                    disabled: isSubmitting
                },
                React.createElement('option', { value: '' }, 'Select Timeline'),
                availabilityOptions.map(option => 
                    React.createElement('option', {
                        key: option,
                        value: option
                    }, option)
                )
            ),
            errors.availabilityDate && React.createElement(
                'span',
                { className: 'error-message' },
                errors.availabilityDate
            )
        ),

        // Navigation/Submit Buttons
        React.createElement(
            'div',
            { className: 'button-group' },
            React.createElement(
                'button',
                {
                    onClick: prevStep,
                    className: 'form-button secondary',
                    disabled: isSubmitting
                },
                'Previous Step'
            ),
            React.createElement(
                'button',
                {
                    onClick: handleSubmit,
                    className: 'form-button',
                    disabled: isSubmitting
                },
                isSubmitting ? 'Submitting...' : 'Submit Request'
            )
        )
    );
};


// Main Form Component
// Update the CarShippingForm component
const CarShippingForm = () => {
    const [step, setStep] = useState(1);
    const [formData, setFormData] = useState({});

    const nextStep = () => setStep(step + 1);
    const prevStep = () => setStep(step - 1);

    return React.createElement(
        'div',
        { className: 'multistep-form-container' },
        React.createElement(ProgressBar, { currentStep: step }),
        step === 1 && React.createElement(Step1, {
            nextStep,
            formData,
            setFormData
        }),
        step === 2 && React.createElement(Step2, {
            nextStep,
            prevStep,
            formData,
            setFormData
        }),
        step === 3 && React.createElement(Step3, {
            prevStep,
            formData,
            setFormData
        })
    );
};

// Initialize form
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('car-shipping-form-container');
    if (container) {
        console.log('Initializing React form');
        const root = ReactDOM.createRoot(container);
        root.render(React.createElement(CarShippingForm));
    } else {
        console.error('Form container not found');
    }
});