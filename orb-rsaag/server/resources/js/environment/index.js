const recaptchaKeys = {
  'production': {
    2: '6LfcJOoZAAAAABVbLG_6eFP71EeR_R0FI_0AZEwD',
    3: '6LdDJ-oZAAAAAHr9L4XB5KuKoMm-XYmqcti2Rytz',
  },

  'preproduction': {
    2: '6LfcJOoZAAAAABVbLG_6eFP71EeR_R0FI_0AZEwD',
    3: '6LdDJ-oZAAAAAHr9L4XB5KuKoMm-XYmqcti2Rytz'
  },

  'default': {
    2: '6LfcJOoZAAAAABVbLG_6eFP71EeR_R0FI_0AZEwD',
    3: '6LdDJ-oZAAAAAHr9L4XB5KuKoMm-XYmqcti2Rytz',
  }
};

const keys = recaptchaKeys[process.env.MIX_APP_ENV] || recaptchaKeys['default'];

export default keys;