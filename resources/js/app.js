const fitBrand = () => {
    const text = document.querySelector('.brand__text');
    const name = text?.querySelector('.brand__name');
    const slogan = text?.querySelector('.brand__slogan');

    if (!name || !slogan) {
        return;
    }

    name.removeAttribute('style');
    slogan.removeAttribute('style');

    const letterSpacing = (element) => parseFloat(getComputedStyle(element).letterSpacing) || 0;
    const glyphWidth = (element) => element.getBoundingClientRect().width - letterSpacing(element);
    const row = text.closest('.site-header__inner');
    const rowStyle = getComputedStyle(row);
    const occupiedBySiblings = Array.from(row.children)
        .filter((element) => !element.contains(text) && element.offsetParent !== null)
        .reduce((sum, element) => sum + element.getBoundingClientRect().width + parseFloat(rowStyle.columnGap || 0), 0);
    const logoAndGap = text.closest('.brand').getBoundingClientRect().width - text.getBoundingClientRect().width;
    const room = row.clientWidth - parseFloat(rowStyle.paddingLeft) - parseFloat(rowStyle.paddingRight) - occupiedBySiblings - logoAndGap;
    const limits = getComputedStyle(text);
    const preferredNameSize = parseFloat(getComputedStyle(name).fontSize) * glyphWidth(slogan) / glyphWidth(name);
    const nameSize = Math.min(parseFloat(limits.getPropertyValue('--name-max')), Math.max(parseFloat(limits.getPropertyValue('--name-min')), preferredNameSize));

    name.style.fontSize = `${nameSize}px`;

    if (glyphWidth(name) > room) {
        name.style.fontSize = `${nameSize * room / glyphWidth(name)}px`;
    }

    const targetWidth = glyphWidth(name);

    if (glyphWidth(slogan) > targetWidth) {
        slogan.style.fontSize = `${Math.max(7, parseFloat(getComputedStyle(slogan).fontSize) * targetWidth / glyphWidth(slogan))}px`;
    }

    const gapsBetweenGlyphs = Math.max(Array.from(slogan.textContent.trim()).length - 1, 1);
    const extraSpacingPerGap = (targetWidth - glyphWidth(slogan)) / gapsBetweenGlyphs;
    const tightestReadableSpacing = -0.4;

    if (extraSpacingPerGap >= tightestReadableSpacing) {
        slogan.style.letterSpacing = `${letterSpacing(slogan) + extraSpacingPerGap}px`;
    } else {
        Object.assign(slogan.style, { maxWidth: `${targetWidth}px`, overflow: 'hidden', textOverflow: 'ellipsis' });
    }
};

let fitBrandTimer;

window.addEventListener('resize', () => {
    clearTimeout(fitBrandTimer);
    fitBrandTimer = setTimeout(fitBrand, 120);
});

document.addEventListener('DOMContentLoaded', fitBrand);
document.fonts?.ready.then(fitBrand);

const slugMaxLength = 120;

const slugify = (value) => value
    .replace(/@/g, '-at-')
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, slugMaxLength)
    .replace(/-+$/g, '');

document.addEventListener('alpine:init', () => {
    window.Alpine.data('segmentEditor', (initialRows) => ({
        rows: initialRows.map((row, index) => ({ ...row, key: index })),
        nextKey: initialRows.length,
        pendingRemoval: null,

        add() {
            const row = { id: null, title: '', url: '', minutes: '', seconds: '', errors: {}, key: this.nextKey++ };

            this.rows.push(row);
            this.$nextTick(() => document.getElementById(`segment-${row.key}-title`)?.focus());
        },

        move(index, offset) {
            const target = index + offset;

            if (target < 0 || target >= this.rows.length) {
                return;
            }

            [this.rows[index], this.rows[target]] = [this.rows[target], this.rows[index]];
        },

        askRemove(index) {
            this.pendingRemoval = index;
            this.$dispatch('open-modal', 'segment-remove');
        },

        confirmRemove() {
            if (this.pendingRemoval !== null) {
                this.rows.splice(this.pendingRemoval, 1);
            }

            this.pendingRemoval = null;
        },
    }));

    window.Alpine.data('slugField', (sourceName, fillAutomatically) => ({
        manual: !fillAutomatically,

        init() {
            const source = this.$el.form?.elements.namedItem(sourceName);

            source?.addEventListener('input', () => {
                if (!this.manual) {
                    this.$el.value = slugify(source.value);
                }
            });
        },

        markManual() {
            this.manual = true;
        },

        normalize() {
            this.$el.value = slugify(this.$el.value);
        },
    }));
});

const pendingLabel = 'Memproses...';

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!form.matches('form[data-pending]')) {
        return;
    }

    if (form.dataset.busy === 'true') {
        event.preventDefault();

        return;
    }

    form.dataset.busy = 'true';

    const submitter = event.submitter;

    if (submitter?.name) {
        const carriedValue = document.createElement('input');
        carriedValue.type = 'hidden';
        carriedValue.name = submitter.name;
        carriedValue.value = submitter.value;
        carriedValue.dataset.carriedSubmitter = 'true';
        form.append(carriedValue);
    }

    form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
        button.dataset.idleLabel = button.innerHTML;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');

        if (!button.classList.contains('btn-icon')) {
            button.textContent = pendingLabel;
        }
    });
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-busy="true"]').forEach((form) => {
        delete form.dataset.busy;
        form.querySelectorAll('input[data-carried-submitter]').forEach((input) => input.remove());

        form.querySelectorAll('button[aria-busy="true"]').forEach((button) => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.innerHTML = button.dataset.idleLabel ?? button.innerHTML;
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[aria-invalid="true"]')?.focus();
});
