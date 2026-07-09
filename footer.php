</div> <style>
    /* Elemen Link Interaktif */
    .footer-links a {
        color: #ffffff !important;
        opacity: 0.75;
        transition: all 0.2s ease;
    }
    .footer-links a:hover {
        opacity: 1;
        padding-left: 6px;
    }
    
    /* Media Sosial Interaktif */
    .footer-social a {
        color: #ffffff !important;
        opacity: 0.8;
        transition: all 0.2s ease;
        display: inline-block;
    }
    .footer-social a:hover {
        opacity: 1;
        transform: translateY(-3px);
    }
</style>

<footer class="text-white pt-5 pb-4 mt-5" style="background-color: #1a202c; border-top: 1px solid #2d3748;">
    <div class="container">
        <div class="row">
            
            <div class="col-md-5 col-lg-5 col-xl-5 mx-auto mt-3 mb-4 mb-md-0">
                <h5 class="text-uppercase mb-4 fw-bold text-white fs-6 tracking-wide">Tentang Kami</h5>
                <p class="text-white-50" style="font-size: 0.9rem; line-height: 1.8; text-align: justify;">
                    Perpustakaan STIKOM adalah sebuah lembaga perpustakaan yang berfokus pada penyediaan layanan pustaka dan referensi ilmiah untuk mendukung kegiatan akademik, penelitian, dan pengembangan di lingkungan kampus STIKOM.
                </p>
            </div>

            <div class="col-md-3 col-lg-3 col-xl-3 mx-auto mt-3 footer-links">
                <h5 class="text-uppercase mb-4 fw-bold text-white fs-6 tracking-wide">Layanan Pustaka</h5>
                <p class="mb-2"><a href="index.php?p=member" class="text-decoration-none small"><i class="fas fa-chevron-right me-2 small text-white-50"></i>Area Anggota</a></p>
                <p class="mb-2"><a href="buku.php" class="text-decoration-none small"><i class="fas fa-chevron-right me-2 small text-white-50"></i>Katalog Online (OPAC)</a></p>
                <p class="mb-2"><a href="#" class="text-decoration-none small"><i class="fas fa-chevron-right me-2 small text-white-50"></i>Layanan E-Book</a></p>
                <p class="mb-0"><a href="#" class="text-decoration-none small"><i class="fas fa-chevron-right me-2 small text-white-50"></i>Sirkulasi Peminjaman</a></p>
            </div>

            <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mt-3">
                <h5 class="text-uppercase mb-4 fw-bold text-white fs-6 tracking-wide">Jam Layanan</h5>
                <div class="d-flex align-items-start mb-3">
                    <i class="fas fa-clock me-2 mt-1 text-white-50"></i>
                    <div class="small">
                        <span class="d-block fw-bold">Senin - Jumat:</span>
                        <span class="text-white-50">08.00 - 20.00 WITA</span>
                    </div>
                </div>
                <div class="d-flex align-items-start mb-3">
                    <i class="fas fa-clock me-2 mt-1 text-white-50"></i>
                    <div class="small">
                        <span class="d-block fw-bold">Sabtu:</span>
                        <span class="text-white-50">08.00 - 15.00 WITA</span>
                    </div>
                </div>
                <div class="d-flex align-items-center mt-4 pt-2 border-top border-secondary">
                    <i class="fas fa-envelope me-2 text-white-50"></i>
                    <span class="small text-white fw-semibold">library@stikom.edu</span>
                </div>
            </div>

        </div>

        <hr class="mb-4 mt-4" style="border-color: rgba(255, 255, 255, 0.1); border-style: solid;">

        <div class="row align-items-center">
            <div class="col-md-7 col-lg-8 text-center text-md-start">
                <p class="text-white-50 mb-0 small">
                    © <?= date('Y') ?> — STIKOM Library. Ditenagai oleh <b class="text-white">SLiMS Custom UI</b>.
                </p>
            </div>
            <div class="col-md-5 col-lg-4 text-center text-md-end mt-3 mt-md-0 footer-social">
                <a href="#" class="me-3" style="font-size: 1.4rem;"><i class="fab fa-facebook-square"></i></a>
                <a href="#" class="me-3" style="font-size: 1.4rem;"><i class="fab fa-twitter-square"></i></a>
                <a href="#" style="font-size: 1.4rem;"><i class="fab fa-instagram-square"></i></a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>