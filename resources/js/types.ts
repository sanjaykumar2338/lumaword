export type User = {
    id: number;
    name: string;
    email: string;
    created_at: string;
};

export type ResourceResponse<T> = {
    data: T;
    message?: string;
};

export type MessageResponse = {
    message: string;
};
