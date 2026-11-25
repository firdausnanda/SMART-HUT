import Select from 'react-select';

export const selectStyles = {
    control: (base, state) => ({
        ...base,
        minHeight: 44,
        borderRadius: '0.75rem',
        backgroundColor: state.isDisabled ? '#f3f4f6' : '#f9fafb',
        borderColor: state.isFocused ? '#1f6e46' : '#e5e7eb',
        boxShadow: state.isFocused ? '0 0 0 1px #1f6e46' : 'none',
        '&:hover': { borderColor: state.isDisabled ? '#e5e7eb' : '#1f6e46' },
    }),
    valueContainer: (base) => ({ ...base, padding: '2px 12px' }),
    singleValue: (base) => ({ ...base, color: '#111827', fontSize: '0.875rem' }),
    placeholder: (base) => ({ ...base, color: '#6b7280', fontSize: '0.875rem' }),
    indicatorSeparator: () => ({ display: 'none' }),
    menu: (base) => ({ ...base, borderRadius: '0.75rem', overflow: 'hidden' }),
    menuPortal: (base) => ({ ...base, zIndex: 9999 }),
};

export default function RegencySelect({
    id,
    regencies,
    value,
    onChange,
    placeholder = 'Pilih kabupaten/kota',
    isDisabled = false,
    isClearable = true,
    required = false,
}) {
    const options = regencies.map((region) => ({ value: region.id, label: region.name }));
    const selected = options.find((option) => String(option.value) === String(value)) || null;

    return <div className="mt-2">
        <Select
            inputId={id}
            name={id}
            options={options}
            value={selected}
            onChange={(option) => onChange(option?.value || '')}
            placeholder={placeholder}
            noOptionsMessage={() => 'Wilayah tidak ditemukan'}
            isDisabled={isDisabled}
            isClearable={isClearable}
            isSearchable
            required={required && !isDisabled}
            styles={selectStyles}
            menuPortalTarget={typeof document !== 'undefined' ? document.body : undefined}
            menuPosition="fixed"
        />
    </div>;
}
