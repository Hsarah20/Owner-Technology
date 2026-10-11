import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['zone', 'input', 'list'];

    connect() {
        this.files = [];
    }

    openPicker() {
        this.inputTarget.click();
    }

    dragOver(event) {
        event.preventDefault();
        this.zoneTarget.classList.add('is-dragover');
    }

    dragLeave(event) {
        event.preventDefault();
        this.zoneTarget.classList.remove('is-dragover');
    }

    drop(event) {
        event.preventDefault();
        this.zoneTarget.classList.remove('is-dragover');
        const files = Array.from(event.dataTransfer.files);
        this.addFiles(files);
    }

    filesSelected() {
        const files = Array.from(this.inputTarget.files);
        this.addFiles(files);
    }

    addFiles(newFiles) {
        for (const file of newFiles) {
            this.files.push(file);
        }
        this.renderList();
        this.syncInput();
    }

    removeFile(index) {
        this.files.splice(index, 1);
        this.renderList();
        this.syncInput();
    }

    renderList() {
        this.listTarget.innerHTML = '';
        this.files.forEach((file, i) => {
            const li = document.createElement('li');
            li.innerHTML = `
                <span>${file.name} <small>(${this.formatSize(file.size)})</small></span>
                <button type="button" data-action="click->upload#removeFile" data-index="${i}">✕</button>
            `;
            li.querySelector('button').addEventListener('click', (e) => {
                e.preventDefault();
                this.removeFile(i);
            });
            this.listTarget.appendChild(li);
        });
    }

    syncInput() {
        const dt = new DataTransfer();
        for (const file of this.files) {
            dt.items.add(file);
        }
        this.inputTarget.files = dt.files;
    }

    formatSize(bytes) {
        if (bytes < 1024) return bytes + ' o';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
        return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
    }
}