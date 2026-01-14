import React from 'react';

export default function ResponsiveTable({ children }) {
    return (
        <div className="w-full overflow-hidden rounded-lg shadow-sm border border-gray-200">
            <div className="w-full overflow-x-auto">
                <table className="w-full whitespace-no-wrap bg-white">
                    {children}
                </table>
            </div>
        </div>
    );
}

ResponsiveTable.Head = ({ children }) => (
    <thead>
        <tr className="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
            {children}
        </tr>
    </thead>
);

ResponsiveTable.Body = ({ children }) => (
    <tbody className="divide-y divide-gray-200">
        {children}
    </tbody>
);

ResponsiveTable.Row = ({ children, className = '' }) => (
    <tr className={`text-gray-700 hover:bg-gray-50 transition-colors duration-150 ${className}`}>
        {children}
    </tr>
);

ResponsiveTable.Cell = ({ children, className = '', isSticky = false }) => {
    const stickyClasses = isSticky ? 'sticky left-0 z-10 bg-inherit shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]' : '';
    return (
        <td className={`px-4 py-3 text-sm ${stickyClasses} ${className}`}>
            {children}
        </td>
    );
};

ResponsiveTable.Heading = ({ children, className = '', isSticky = false }) => {
     const stickyClasses = isSticky ? 'sticky left-0 z-10 bg-gray-50 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]' : '';
    return (
        <th className={`px-4 py-3 ${stickyClasses} ${className}`}>
            {children}
        </th>
    );
};
