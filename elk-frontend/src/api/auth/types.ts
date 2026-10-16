import { z } from "zod";

// Zod schema for validation
export const LoginBodySchema = z.object({
    login: z.string().min(1, "Обязательное поле").email("Неверный формат email"),
    password: z.string().min(8, "Минимум 8 символов").max(32, "Максимум 32 символов").regex(/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^\da-zA-Z]).*$/, "Пароль должен содержать минимум 8 символов, 1 заглавную букву, 1 строчную букву, 1 цифру и 1 специальный символ"),
});

// TypeScript types for form
export type LoginBody = z.infer<typeof LoginBodySchema>;
