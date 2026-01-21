</main>
        </div> </div> <script>
        // Gestion du menu mobile
        const sidebar = document.getElementById('sidebar');
        const menuBtn = document.querySelector('button.text-gray-500');
        
        if(menuBtn){
            menuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });
        }
    </script>
</body>
</html>
