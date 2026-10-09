import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['output'];
    static values = { name: String };

    greet() {
        const name = this.hasNameValue ? this.nameValue : 'monde';
        this.outputTarget.textContent = `Bonjour ${name} !`;
    }
}
