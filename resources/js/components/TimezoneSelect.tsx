import { useEffect, useMemo, useState, type KeyboardEvent, type PointerEvent } from 'react';
import { filterTimezoneOptions } from '../lib/timezones.js';
import type { TimezoneOption } from '../types';

type TimezoneSelectProps = {
    id: string;
    label: string;
    value: string;
    options: TimezoneOption[];
    onChange: (timezone: string) => void;
    error?: string;
};

export function TimezoneSelect({ id, label, value, options, onChange, error }: TimezoneSelectProps) {
    const selectedOption = options.find((option) => option.value === value);
    const selectedLabel = selectedOption?.label ?? value;
    const [query, setQuery] = useState(selectedLabel);
    const [isOpen, setIsOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const filteredOptions = useMemo(() => filterTimezoneOptions(options, query), [options, query]);
    const listboxId = `${id}-options`;

    useEffect(() => {
        if (!isOpen) {
            setQuery(selectedLabel);
        }
    }, [isOpen, selectedLabel]);

    const chooseOption = (option: TimezoneOption) => {
        onChange(option.value);
        setQuery(option.label);
        setIsOpen(false);
        setActiveIndex(0);
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Escape') {
            setQuery(selectedLabel);
            setIsOpen(false);
            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            setIsOpen(true);
            setActiveIndex((currentIndex) => {
                const direction = event.key === 'ArrowDown' ? 1 : -1;
                const nextIndex = currentIndex + direction;

                return Math.min(Math.max(nextIndex, 0), Math.max(filteredOptions.length - 1, 0));
            });
            return;
        }

        if (event.key === 'Enter' && isOpen && filteredOptions[activeIndex]) {
            event.preventDefault();
            chooseOption(filteredOptions[activeIndex]);
        }
    };

    const handleOptionPointerDown = (event: PointerEvent<HTMLButtonElement>, option: TimezoneOption) => {
        event.preventDefault();
        chooseOption(option);
    };

    return (
        <div className="grid min-w-0 gap-2 text-sm font-semibold">
            <label htmlFor={id}>{label}</label>
            <div className="relative">
                <input
                    id={id}
                    type="search"
                    role="combobox"
                    autoComplete="off"
                    value={query}
                    aria-autocomplete="list"
                    aria-controls={listboxId}
                    aria-expanded={isOpen}
                    aria-activedescendant={isOpen && filteredOptions[activeIndex] ? `${id}-option-${activeIndex}` : undefined}
                    aria-invalid={Boolean(error)}
                    aria-describedby={error ? `${id}-error` : undefined}
                    onFocus={(event) => {
                        event.currentTarget.select();
                        setIsOpen(true);
                    }}
                    onBlur={() => {
                        setQuery(selectedLabel);
                        setIsOpen(false);
                    }}
                    onChange={(event) => {
                        setQuery(event.target.value);
                        setIsOpen(true);
                        setActiveIndex(0);
                    }}
                    onKeyDown={handleKeyDown}
                    className="h-11 w-full min-w-0 rounded-xl border border-stone-300 bg-white px-3 font-normal text-ink outline-none focus:border-moss"
                />
                {isOpen && (
                    <div id={listboxId} role="listbox" className="absolute z-20 mt-2 max-h-64 w-full min-w-0 overflow-y-auto rounded-xl border border-stone-200 bg-white p-1 shadow-lg">
                        {filteredOptions.length > 0 ? filteredOptions.map((option, index) => (
                            <button
                                id={`${id}-option-${index}`}
                                key={option.value}
                                type="button"
                                role="option"
                                aria-selected={option.value === value}
                                tabIndex={-1}
                                onPointerDown={(event) => handleOptionPointerDown(event, option)}
                                className={`block w-full rounded-lg px-3 py-2 text-left text-sm font-normal hover:bg-stone-100 ${index === activeIndex ? 'bg-stone-100' : ''}`}
                            >
                                {option.label}
                            </button>
                        )) : <p className="px-3 py-2 text-sm font-normal text-stone-500">No matching timezones.</p>}
                    </div>
                )}
            </div>
            {error && <span id={`${id}-error`} className="text-xs text-red-700">{error}</span>}
        </div>
    );
}
