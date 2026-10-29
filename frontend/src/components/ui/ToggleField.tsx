import type { UseFormRegisterReturn } from 'react-hook-form';

interface ToggleFieldProps {
    registration: UseFormRegisterReturn;
    checked: boolean;
}

export default function ToggleField({ registration, checked }: ToggleFieldProps) {
    return (
        <>
            <input
                type="checkbox"
                className="toggle toggle-primary"
                checked={checked}
                {...registration}
            />
            <span className="text-sm font-light">{checked ? 'Sí' : 'No'}</span>
        </>
    );
}
