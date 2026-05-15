
function tambahBahan() {
    var container = document.getElementById('bahan-list');

    var row = document.createElement('div');
    row.className = 'bahan-row';

    row.innerHTML = `
        <div>
            <label class="bahan-row-label">Nama Bahan</label>
            <input type="text" name="bahan_nama[]" placeholder="Nama bahan">
        </div>
        <div>
            <label class="bahan-row-label">Tipe</label>
            <select name="bahan_tipe[]">
                <option value="Limbah organik">Limbah organik</option>
                <option value="Sumber gula">Sumber gula</option>
                <option value="Pelarut">Pelarut</option>
                <option value="Lainnya">Lainnya</option>
            </select>
        </div>
        <div>
            <label class="bahan-row-label">Berat (gram)</label>
            <input type="number" name="bahan_berat[]" placeholder="500">
        </div>
        <div>
            <button type="button" class="btn-hapus-bahan" onclick="hapusBaris(this)">✕</button>
        </div>
    `;

    container.appendChild(row);
}

function hapusBaris(tombol) {
    var row = tombol.closest('.bahan-row');
    if (row) {
        row.remove();
    }
}

function konfirmasiHapus(pesan) {
    return confirm(pesan || 'Yakin mau dihapus?');
}

document.addEventListener('DOMContentLoaded', function() {

    var flashMsg = document.querySelector('.flash-msg');
    if (flashMsg) {
        setTimeout(function() {
            flashMsg.style.transition = 'opacity 0.5s';
            flashMsg.style.opacity = '0';

            setTimeout(function() {
                flashMsg.remove();
            }, 500);
        }, 3000);
    }

});
