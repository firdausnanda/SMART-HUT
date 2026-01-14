import { forwardRef, useEffect, useRef } from 'react';

export default forwardRef(function TextInput({ type = 'text', className = '', isFocused = false, ...props }, ref) {
    const input = ref ? ref : useRef();

    useEffect(() => {
        if (isFocused) {
            input.current.focus();
        }
    }, []);

    return (
        <input
            {...props}
            type={type}
            className={
                'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-600 rounded-xl shadow-sm transition-all duration-200 ' +
                className
            }
            ref={input}
        />
    );
});
