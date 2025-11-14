<?php
// functions/jasa_process.php
require_once __DIR__ . '/../config/database.php';

/**
 * Mendapatkan semua proses jasa
 */
function get_all_jasa_process($conn) {
    $sql = "SELECT jp.*, 
                   b.customer_id, b.service_id, b.booking_date, b.booking_time,
                   u.name as barber_name,
                   c.name as customer_name,
                   s.name as service_name, s.duration, s.price
            FROM jasa_process jp
            JOIN bookings b ON jp.booking_id = b.id
            JOIN users u ON jp.barber_id = u.id
            JOIN users c ON b.customer_id = c.id
            JOIN services s ON b.service_id = s.id
            ORDER BY jp.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    $processes = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $processes[] = $row;
        }
    }
    
    return $processes;
}

/**
 * Mendapatkan proses jasa berdasarkan ID
 */
function get_jasa_process_by_id($conn, $id) {
    $id = mysqli_real_escape_string($conn, $id);
    
    $sql = "SELECT jp.*, 
                   b.customer_id, b.service_id, b.booking_date, b.booking_time,
                   u.name as barber_name,
                   c.name as customer_name,
                   s.name as service_name, s.duration, s.price
            FROM jasa_process jp
            JOIN bookings b ON jp.booking_id = b.id
            JOIN users u ON jp.barber_id = u.id
            JOIN users c ON b.customer_id = c.id
            JOIN services s ON b.service_id = s.id
            WHERE jp.id = '$id'";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Mendapatkan proses jasa berdasarkan barber ID
 */
function get_jasa_process_by_barber($conn, $barber_id) {
    $barber_id = mysqli_real_escape_string($conn, $barber_id);
    
    $sql = "SELECT jp.*, 
                   b.customer_id, b.service_id, b.booking_date, b.booking_time,
                   u.name as barber_name,
                   c.name as customer_name,
                   s.name as service_name, s.duration, s.price
            FROM jasa_process jp
            JOIN bookings b ON jp.booking_id = b.id
            JOIN users u ON jp.barber_id = u.id
            JOIN users c ON b.customer_id = c.id
            JOIN services s ON b.service_id = s.id
            WHERE jp.barber_id = '$barber_id'
            ORDER BY jp.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    $processes = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $processes[] = $row;
        }
    }
    
    return $processes;
}

/**
 * Mendapatkan proses jasa berdasarkan status
 */
function get_jasa_process_by_status($conn, $status) {
    $status = mysqli_real_escape_string($conn, $status);
    
    $sql = "SELECT jp.*, 
                   b.customer_id, b.service_id, b.booking_date, b.booking_time,
                   u.name as barber_name,
                   c.name as customer_name,
                   s.name as service_name, s.duration, s.price
            FROM jasa_process jp
            JOIN bookings b ON jp.booking_id = b.id
            JOIN users u ON jp.barber_id = u.id
            JOIN users c ON b.customer_id = c.id
            JOIN services s ON b.service_id = s.id
            WHERE jp.status = '$status'
            ORDER BY jp.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    $processes = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $processes[] = $row;
        }
    }
    
    return $processes;
}

/**
 * Membuat proses jasa baru
 */
function create_jasa_process($conn, $booking_id, $barber_id, $notes = '') {
    $booking_id = mysqli_real_escape_string($conn, $booking_id);
    $barber_id = mysqli_real_escape_string($conn, $barber_id);
    $notes = mysqli_real_escape_string($conn, $notes);
    
    // Cek apakah booking sudah ada di proses jasa
    $check_sql = "SELECT id FROM jasa_process WHERE booking_id = '$booking_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        return [
            'success' => false,
            'message' => 'Booking ini sudah ada dalam proses jasa'
        ];
    }
    
    $sql = "INSERT INTO jasa_process (booking_id, barber_id, status, notes) 
            VALUES ('$booking_id', '$barber_id', 'waiting', '$notes')";
    
    if (mysqli_query($conn, $sql)) {
        return [
            'success' => true,
            'message' => 'Proses jasa berhasil dibuat',
            'id' => mysqli_insert_id($conn)
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Gagal membuat proses jasa: ' . mysqli_error($conn)
    ];
}

/**
 * Memulai proses jasa
 */
function start_jasa_process($conn, $id) {
    $id = mysqli_real_escape_string($conn, $id);
    $start_time = date('Y-m-d H:i:s');
    
    $sql = "UPDATE jasa_process 
            SET status = 'in_progress', 
                start_time = '$start_time'
            WHERE id = '$id' AND status = 'waiting'";
    
    if (mysqli_query($conn, $sql)) {
        if (mysqli_affected_rows($conn) > 0) {
            return [
                'success' => true,
                'message' => 'Proses jasa berhasil dimulai'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Proses jasa tidak ditemukan atau sudah dimulai'
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Gagal memulai proses jasa: ' . mysqli_error($conn)
    ];
}

/**
 * Menyelesaikan proses jasa
 */
function complete_jasa_process($conn, $id) {
    $id = mysqli_real_escape_string($conn, $id);
    $end_time = date('Y-m-d H:i:s');
    
    $sql = "UPDATE jasa_process 
            SET status = 'completed', 
                end_time = '$end_time'
            WHERE id = '$id' AND status = 'in_progress'";
    
    if (mysqli_query($conn, $sql)) {
        if (mysqli_affected_rows($conn) > 0) {
            // Update booking status juga
            $update_booking = "UPDATE bookings b
                              JOIN jasa_process jp ON b.id = jp.booking_id
                              SET b.status = 'completed'
                              WHERE jp.id = '$id'";
            mysqli_query($conn, $update_booking);
            
            return [
                'success' => true,
                'message' => 'Proses jasa berhasil diselesaikan'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Proses jasa tidak ditemukan atau belum dimulai'
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Gagal menyelesaikan proses jasa: ' . mysqli_error($conn)
    ];
}

/**
 * Membatalkan proses jasa
 */
function cancel_jasa_process($conn, $id, $notes = '') {
    $id = mysqli_real_escape_string($conn, $id);
    $notes = mysqli_real_escape_string($conn, $notes);
    
    $sql = "UPDATE jasa_process 
            SET status = 'cancelled', 
                notes = CONCAT(notes, '\nDibatalkan: $notes')
            WHERE id = '$id' AND status IN ('waiting', 'in_progress')";
    
    if (mysqli_query($conn, $sql)) {
        if (mysqli_affected_rows($conn) > 0) {
            // Update booking status juga
            $update_booking = "UPDATE bookings b
                              JOIN jasa_process jp ON b.id = jp.booking_id
                              SET b.status = 'cancelled'
                              WHERE jp.id = '$id'";
            mysqli_query($conn, $update_booking);
            
            return [
                'success' => true,
                'message' => 'Proses jasa berhasil dibatalkan'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Proses jasa tidak ditemukan atau sudah selesai'
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Gagal membatalkan proses jasa: ' . mysqli_error($conn)
    ];
}

/**
 * Update notes proses jasa
 */
function update_jasa_process_notes($conn, $id, $notes) {
    $id = mysqli_real_escape_string($conn, $id);
    $notes = mysqli_real_escape_string($conn, $notes);
    
    $sql = "UPDATE jasa_process 
            SET notes = '$notes'
            WHERE id = '$id'";
    
    if (mysqli_query($conn, $sql)) {
        return [
            'success' => true,
            'message' => 'Catatan berhasil diupdate'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Gagal update catatan: ' . mysqli_error($conn)
    ];
}

/**
 * Mendapatkan statistik proses jasa
 */
function get_jasa_process_stats($conn, $barber_id = null) {
    $where = $barber_id ? "WHERE barber_id = '" . mysqli_real_escape_string($conn, $barber_id) . "'" : "";
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM jasa_process
            $where";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Mendapatkan durasi rata-rata proses jasa
 */
function get_average_process_duration($conn, $barber_id = null) {
    $where = $barber_id ? "AND barber_id = '" . mysqli_real_escape_string($conn, $barber_id) . "'" : "";
    
    $sql = "SELECT 
                AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_duration_minutes
            FROM jasa_process
            WHERE status = 'completed' 
            AND start_time IS NOT NULL 
            AND end_time IS NOT NULL
            $where";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['avg_duration_minutes'];
    }
    
    return 0;
}

/**
 * Mendapatkan booking yang siap diproses (belum ada di jasa_process)
 */
function get_bookings_ready_to_process($conn) {
    $sql = "SELECT b.*, 
                   u.name as customer_name,
                   s.name as service_name, s.duration, s.price
            FROM bookings b
            JOIN users u ON b.customer_id = u.id
            JOIN services s ON b.service_id = s.id
            LEFT JOIN jasa_process jp ON b.id = jp.booking_id
            WHERE b.status = 'confirmed' 
            AND jp.id IS NULL
            ORDER BY b.booking_date, b.booking_time";
    
    $result = mysqli_query($conn, $sql);
    $bookings = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $bookings[] = $row;
        }
    }
    
    return $bookings;
}
?>
