import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', {
  state: () => ({
    visible: false,
    message: '',
    color: 'success',
  }),
  actions: {
    show(message, color = 'success') {
      this.message = message
      this.color = color
      this.visible = true
    },
  },
})
