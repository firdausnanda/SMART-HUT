import { useRef } from 'react';
import TextInput from './TextInput';

export default function CurrencyInput({ value, onChange, placeholder, className, required, ...props }) {
  const inputRef = useRef(null);

  // Format number to '1.000.000' style for display
  const formatDisplay = (val) => {
    if (val === '' || val === null || val === undefined) return '';
    return new Intl.NumberFormat('id-ID', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(val);
  };

  // Handle user input
  const handleChange = (e) => {
    const inputValue = e.target.value;

    // Remove all non-digit characters
    const cleanValue = inputValue.replace(/\D/g, '');

    if (cleanValue === '') {
      onChange('');
      return;
    }

    const numberValue = parseInt(cleanValue, 10);
    onChange(numberValue);
  };

  return (
    <div className="relative">
      <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <span className="text-gray-500 font-bold">Rp</span>
      </div>
      <TextInput
        {...props}
        ref={inputRef}
        type="text"
        className={`pl-10 w-full ${className}`}
        value={formatDisplay(value)}
        onChange={handleChange}
        placeholder={placeholder || '0'}
        required={required}
      />
    </div>
  );
}
