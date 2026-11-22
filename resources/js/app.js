import './charts';

// Load the editor only on lesson forms, including forms reached through Livewire.
const loadReadingBuilder = () => {
    if (document.querySelector('[data-section-builder]')) {
        import('./reading-section-builder').catch(error => console.error('Reading tools could not load', error));
    }
};
document.addEventListener('livewire:navigated', loadReadingBuilder);
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', loadReadingBuilder, { once: true });
else loadReadingBuilder();
