import {url} from "vee-validate/dist/rules.esm";
import {is, is_not, length} from "vee-validate/dist/rules.esm";
import {pluralization} from "@/js/helpers";

const validators = {
	fileType: {
		getMessage(field, args) {
			return `Поле «${field}» должно иметь иной тип`;
		},

		validate(value, args) {
			return /(\.jpg|\.jpeg|\.png|\.webp)$/i.test(value[0].name);
		},
	},

	cyrillic: {
		getMessage(field, args) {
			return `Поле «${field}» должно состоять из русских и латинских букв`;
		},

		validate(value, args) {
			return /^[а-яА-ЯёЁa-zA-Z]+(-[а-яА-ЯёЁa-zA-Z]+)*$/u.test(value);
		},
	},

	onlyCyrillic: {
		getMessage(field, args) {
			return `Поле «${field}» должно состоять из русских букв`;
		},

		validate(value, args) {
			return /^[а-яА-ЯёЁ]+(-[а-яА-ЯёЁ]+)*$/u.test(value);
		},
	},

	phone: {
		getMessage(field, args) {
			return `Поле «${field}» должно соответствовать формату +7 (___) ___-__-__`;
		},

		validate(value, args) {
			return /^((\+7 \(\d{3}\) \d{3}-\d{2}-\d{2})|)$/.test(value);
		},
	},

	gender: {
		getMessage(field, args) {
			return "Пол может быть мужским или женским";
		},

		validate(value, args) {
			return ["male", "female"].includes(value);
		},
	},

	is_equal: {
		getMessage(field, args) {
			return "Необходимо отметить этот пункт";
		},

		validate(value, args) {
			return value;
		},
	},

	alpha_lat_dash: {
		getMessage(field, args) {
			return `Поле «${field}» должно состоять из латинских букв, нижних подчёркиваний и цифр`;
		},

		validate(value, args) {
			return /^[a-zA-Z_0-9]*$/u.test(value);
		},
	},

	url_comma_string: {
		getMessage(field, args) {
			return `Поле «${field}» должно состоять из корректных url-адресов, разделенных запятыми`;
		},

		validate(value, args) {
			const urls = value.split(",");
			return urls.every(link => url.validate(link.trim(), args));
		},
	},

	password: {
		getMessage(field, args) {
			return field.indexOf("ароль") !== -1 ? `«${field}» должен быть не менее 6 символов` : `«${field}» должно быть не менее 6 символов`;
		},

		validate(value, args) {
			return value && value.length >= 6;
		},
	},

	password_confirmation: {
		getMessage(field, args) {
			return "Введенные пароли не совпадают";
		},

		validate(value, args) {
			return is.validate(value, args);
		},
	},

	new_password: {
		getMessage(field, args) {
			return "Новый пароль не может совпадать со старым";
		},

		validate(value, args) {
			return is_not.validate(value, args);
		},
	},

	length_numbers: {
		getMessage(field, args) {
			return `Поле «${field}» должно содержать ${args[0]} ${pluralization(args[0], ["цифра", "цифры", "цифр"])}`;
		},

		validate(value, args) {
			const numberString = value.length ? value.replace(/[^\d]/g, "") : "";
			return length.validate(numberString, args);
		},
	},
};

export default validators;
