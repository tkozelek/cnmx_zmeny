<x-layout>
    <!-- Hero Section -->
    @include('welcome.partials._hero')

    <!-- How It Works Section -->
    @include('welcome.partials._howitworks')

    <!-- Benefits Section -->
    @include('welcome.partials._benefits')

    <!-- CTA / Register Section -->
    @include('welcome.partials._cta')

<!-- Footer -->
<footer class="bg-gray-900 border-t border-gray-800">
    <div class="container mx-auto py-6 px-4 text-center text-gray-500 dark:text-gray-400">
        <p>&copy; {{ now()->year }} Cinemax Zmeny. Všetky práva vyhradené.</p>
    </div>
</footer>

<script>
    function revealSections() {
        const reveals = document.querySelectorAll('.reveal');
        for (let i = 0; i < reveals.length; i++) {
            const windowHeight = window.innerHeight;
            const elementTop = reveals[i].getBoundingClientRect().top;
            const elementVisible = 100;
            if (elementTop < windowHeight - elementVisible) {
                reveals[i].classList.add('active');
            }
        }
    }
    window.addEventListener('scroll', revealSections);
    revealSections();
</script>

</x-layout>
</html>
