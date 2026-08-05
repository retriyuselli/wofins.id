import CoreLocation
import MapKit
import PhotosUI
import SwiftUI
import UIKit

struct AttendanceView: View {
    @EnvironmentObject private var appState: AppState
    @StateObject private var location = LocationManager()

    @State private var hariIni: AbsensiHariIniData?
    @State private var lokasiKantor: [LokasiAbsensiItem] = []
    @State private var cekLokasi: CekLokasiData?
    @State private var ringkasan: AbsensiRingkasanData?
    @State private var riwayat: [AbsensiItem] = []
    @State private var capturedPhoto: UIImage?
    @State private var showCamera = false
    @State private var pendingAction: AttendanceAction?
    @State private var isLoading = false
    @State private var isSubmitting = false
    @State private var errorMessage: String?
    @State private var successMessage: String?

    private let navy = Color(red: 0.0, green: 0.239, blue: 0.475)
    private let gold = Color(red: 1.0, green: 0.725, blue: 0.0)

    private enum AttendanceAction {
        case masuk, pulang
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    statusCard
                    mapCard
                    photoCard
                    actionButtons
                    ringkasanCard
                    riwayatCard
                }
                .padding(16)
            }
            .background(Color(red: 0.969, green: 0.976, blue: 0.988).ignoresSafeArea())
            .navigationTitle("Absensi")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        Task { await refresh() }
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
            }
            .task {
                location.requestPermission()
                await refresh()
            }
            .onChange(of: location.coordinate?.latitude) { _, _ in
                Task { await recheckLocation() }
            }
            .sheet(isPresented: $showCamera) {
                CameraPicker { image in
                    capturedPhoto = image
                    showCamera = false
                    if let pendingAction {
                        Task { await submit(pendingAction) }
                    }
                }
                .ignoresSafeArea()
            }
        }
    }

    private var statusCard: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Hari ini")
                .font(.poppins(.headline))
                .foregroundStyle(navy)
            Text(hariIni?.tanggal ?? "-")
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)

            HStack(spacing: 12) {
                statusPill("Masuk", value: formatTime(hariIni?.absensi?.jam_masuk))
                statusPill("Pulang", value: formatTime(hariIni?.absensi?.jam_pulang))
            }

            if let status = hariIni?.absensi?.status {
                Text("Status: \(status.capitalized)")
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(navy)
            }

            if let errorMessage {
                Text(errorMessage).font(.poppins(.footnote)).foregroundStyle(WofinsTheme.danger)
            }
            if let successMessage {
                Text(successMessage).font(.poppins(.footnote)).foregroundStyle(.green)
            }
        }
        .padding()
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color.white)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    private var mapCard: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Lokasi")
                .font(.poppins(.headline))
                .foregroundStyle(navy)

            AttendanceMapView(
                userCoordinate: location.coordinate,
                offices: lokasiKantor
            )
            .frame(height: 220)
            .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))

            if let cekLokasi {
                HStack {
                    Circle()
                        .fill(cekLokasi.dalam_radius ? Color.green : Color.red)
                        .frame(width: 10, height: 10)
                    if cekLokasi.dalam_radius {
                        Text("Dalam radius kantor")
                            .font(.poppins(.subheadline, weight: .medium))
                            .foregroundStyle(.green)
                    } else {
                        Text("Terlalu jauh (±\(cekLokasi.jarak_meter ?? 0) m)")
                            .font(.poppins(.subheadline, weight: .medium))
                            .foregroundStyle(.red)
                    }
                }
            } else if location.authorizationDenied {
                Text("Aktifkan izin lokasi untuk absensi.")
                    .font(.poppins(.footnote))
                    .foregroundStyle(WofinsTheme.danger)
            } else {
                Text("Mengambil lokasi…")
                    .font(.poppins(.footnote))
                    .foregroundStyle(WofinsTheme.muted)
            }
        }
        .padding()
        .background(Color.white)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    private var photoCard: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Foto absensi")
                .font(.poppins(.headline))
                .foregroundStyle(navy)

            if let capturedPhoto {
                Image(uiImage: capturedPhoto)
                    .resizable()
                    .scaledToFill()
                    .frame(height: 160)
                    .frame(maxWidth: .infinity)
                    .clipped()
                    .clipShape(RoundedRectangle(cornerRadius: 12, style: .continuous))
            } else {
                RoundedRectangle(cornerRadius: 12, style: .continuous)
                    .stroke(Color.gray.opacity(0.3), style: StrokeStyle(lineWidth: 1, dash: [6]))
                    .frame(height: 100)
                    .overlay {
                        Text("Foto diambil dari kamera saat absen")
                            .font(.poppins(.footnote))
                            .foregroundStyle(WofinsTheme.muted)
                    }
            }
        }
        .padding()
        .background(Color.white)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    private var actionButtons: some View {
        VStack(spacing: 10) {
            Button {
                startAbsen(.masuk)
            } label: {
                labelButton(isSubmitting && pendingAction == .masuk ? "Memproses…" : "Absen Masuk")
            }
            .buttonStyle(.plain)
            .disabled(!(hariIni?.bisa_masuk ?? true) || !canAbsen || isSubmitting)
            .opacity((hariIni?.bisa_masuk ?? true) && canAbsen ? 1 : 0.5)

            Button {
                startAbsen(.pulang)
            } label: {
                HStack {
                    Spacer()
                    if isSubmitting && pendingAction == .pulang {
                        ProgressView().tint(navy)
                    } else {
                        Text("Absen Pulang")
                            .font(.poppins(.body, weight: .bold))
                    }
                    Spacer()
                }
                .frame(height: 50)
                .foregroundStyle(navy)
                .background(Color.white)
                .overlay(RoundedRectangle(cornerRadius: 14).stroke(navy, lineWidth: 1.5))
            }
            .buttonStyle(.plain)
            .disabled(!(hariIni?.bisa_pulang ?? false) || !canAbsen || isSubmitting)
            .opacity((hariIni?.bisa_pulang ?? false) && canAbsen ? 1 : 0.5)
        }
    }

    private var ringkasanCard: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Ringkasan bulan ini")
                .font(.poppins(.headline))
                .foregroundStyle(navy)
            if let ringkasan {
                LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 8) {
                    metric("Hadir", "\(ringkasan.hadir ?? 0)")
                    metric("Terlambat", "\(ringkasan.terlambat ?? 0)")
                    metric("Alfa", "\(ringkasan.alfa ?? 0)")
                    metric("Cuti", "\(ringkasan.cuti ?? 0)")
                }
            } else {
                Text("-").foregroundStyle(WofinsTheme.muted)
            }
        }
        .padding()
        .background(Color.white)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    private var riwayatCard: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Riwayat")
                .font(.poppins(.headline))
                .foregroundStyle(navy)
            if riwayat.isEmpty {
                Text("Belum ada data.").foregroundStyle(WofinsTheme.muted)
            } else {
                ForEach(riwayat.prefix(10)) { item in
                    HStack {
                        VStack(alignment: .leading, spacing: 2) {
                            Text(item.tanggal ?? "-").font(.poppins(.subheadline, weight: .semibold))
                            Text("\(formatTime(item.jam_masuk)) – \(formatTime(item.jam_pulang))")
                                .font(.poppins(.caption))
                                .foregroundStyle(WofinsTheme.muted)
                        }
                        Spacer()
                        Text((item.status ?? "-").capitalized)
                            .font(.poppins(.caption, weight: .semibold))
                            .foregroundStyle(navy)
                    }
                    .padding(.vertical, 4)
                    Divider()
                }
            }
        }
        .padding()
        .background(Color.white)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    private var canAbsen: Bool {
        guard location.coordinate != nil else { return false }
        if hariIni?.pengaturan?.tolak_jika_di_luar_radius == true {
            return cekLokasi?.dalam_radius == true
        }
        return true
    }

    private func labelButton(_ title: String) -> some View {
        HStack {
            Spacer()
            if isSubmitting && pendingAction == .masuk {
                ProgressView().tint(.white)
            } else {
                Text(title).font(.poppins(.body, weight: .bold))
            }
            Spacer()
        }
        .frame(height: 52)
        .foregroundStyle(.white)
        .background(navy)
        .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
        .overlay(alignment: .bottom) {
            Capsule().fill(gold).frame(height: 3).padding(.horizontal, 20).offset(y: 1)
        }
    }

    private func statusPill(_ title: String, value: String) -> some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(title).font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
            Text(value).font(.poppins(.subheadline, weight: .semibold)).foregroundStyle(navy)
        }
        .padding(10)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color(red: 0.969, green: 0.976, blue: 0.988))
        .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))
    }

    private func metric(_ title: String, _ value: String) -> some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(title).font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
            Text(value).font(.poppins(.title3, weight: .bold)).foregroundStyle(navy)
        }
        .padding(10)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color(red: 0.969, green: 0.976, blue: 0.988))
        .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))
    }

    private func startAbsen(_ action: AttendanceAction) {
        errorMessage = nil
        successMessage = nil
        pendingAction = action
        capturedPhoto = nil
        showCamera = true
    }

    private func submit(_ action: AttendanceAction) async {
        guard let coordinate = location.coordinate else {
            errorMessage = "Lokasi belum tersedia."
            return
        }
        guard let capturedPhoto, let data = capturedPhoto.jpegData(compressionQuality: 0.75) else {
            errorMessage = "Foto kamera wajib."
            return
        }

        isSubmitting = true
        defer { isSubmitting = false }

        do {
            let item: AbsensiItem
            switch action {
            case .masuk:
                item = try await appState.api.absensiMasuk(
                    lintang: coordinate.latitude,
                    bujur: coordinate.longitude,
                    akurasi: location.accuracy,
                    fotoJPEG: data
                )
                successMessage = "Absen masuk berhasil."
            case .pulang:
                item = try await appState.api.absensiPulang(
                    lintang: coordinate.latitude,
                    bujur: coordinate.longitude,
                    akurasi: location.accuracy,
                    fotoJPEG: data
                )
                successMessage = "Absen pulang berhasil."
            }
            _ = item
            pendingAction = nil
            await refresh()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    private func refresh() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            async let today = appState.api.absensiHariIni()
            async let lokasi = appState.api.absensiLokasi()
            async let summary = appState.api.absensiRingkasan()
            async let history = appState.api.absensiRiwayat()
            hariIni = try await today
            lokasiKantor = try await lokasi
            ringkasan = try await summary
            riwayat = try await history
            await recheckLocation()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    private func recheckLocation() async {
        guard let coordinate = location.coordinate else { return }
        do {
            cekLokasi = try await appState.api.absensiCekLokasi(
                lintang: coordinate.latitude,
                bujur: coordinate.longitude
            )
        } catch {
            // keep previous status
        }
    }

    private func formatTime(_ iso: String?) -> String {
        guard let iso, let date = ISO8601DateFormatter().date(from: iso) else { return "-" }
        let formatter = DateFormatter()
        formatter.dateFormat = "HH:mm"
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        return formatter.string(from: date)
    }
}

// MARK: - Map

private struct AttendanceMapView: View {
    let userCoordinate: CLLocationCoordinate2D?
    let offices: [LokasiAbsensiItem]

    var body: some View {
        Map {
            if let userCoordinate {
                Annotation("Anda", coordinate: userCoordinate) {
                    Image(systemName: "person.crop.circle.fill")
                        .foregroundStyle(.blue)
                        .font(.poppins(.title2))
                }
            }

            ForEach(offices) { office in
                let center = CLLocationCoordinate2D(latitude: office.lintang, longitude: office.bujur)
                Annotation(office.nama ?? "Kantor", coordinate: center) {
                    Image(systemName: "building.2.fill")
                        .foregroundStyle(Color(red: 0.0, green: 0.239, blue: 0.475))
                }
                MapCircle(center: center, radius: CLLocationDistance(office.radius_meter))
                    .foregroundStyle(Color.green.opacity(0.18))
                    .stroke(Color.green.opacity(0.7), lineWidth: 2)
            }
        }
        .mapStyle(.standard)
    }
}

// MARK: - Location

@MainActor
final class LocationManager: NSObject, ObservableObject, CLLocationManagerDelegate {
    @Published var coordinate: CLLocationCoordinate2D?
    @Published var accuracy: Double?
    @Published var authorizationDenied = false

    private let manager = CLLocationManager()

    override init() {
        super.init()
        manager.delegate = self
        manager.desiredAccuracy = kCLLocationAccuracyBest
    }

    func requestPermission() {
        manager.requestWhenInUseAuthorization()
        manager.startUpdatingLocation()
    }

    nonisolated func locationManagerDidChangeAuthorization(_ manager: CLLocationManager) {
        Task { @MainActor in
            switch manager.authorizationStatus {
            case .authorizedAlways, .authorizedWhenInUse:
                authorizationDenied = false
                manager.startUpdatingLocation()
            case .denied, .restricted:
                authorizationDenied = true
            default:
                break
            }
        }
    }

    nonisolated func locationManager(_ manager: CLLocationManager, didUpdateLocations locations: [CLLocation]) {
        guard let latest = locations.last else { return }
        Task { @MainActor in
            coordinate = latest.coordinate
            accuracy = latest.horizontalAccuracy
        }
    }
}

// MARK: - Camera (no gallery)

struct CameraPicker: UIViewControllerRepresentable {
    var onImage: (UIImage) -> Void

    func makeUIViewController(context: Context) -> UIImagePickerController {
        let picker = UIImagePickerController()
        picker.sourceType = .camera
        picker.cameraCaptureMode = .photo
        picker.delegate = context.coordinator
        picker.allowsEditing = false
        return picker
    }

    func updateUIViewController(_ uiViewController: UIImagePickerController, context: Context) {}

    func makeCoordinator() -> Coordinator {
        Coordinator(onImage: onImage)
    }

    final class Coordinator: NSObject, UINavigationControllerDelegate, UIImagePickerControllerDelegate {
        let onImage: (UIImage) -> Void

        init(onImage: @escaping (UIImage) -> Void) {
            self.onImage = onImage
        }

        func imagePickerController(
            _ picker: UIImagePickerController,
            didFinishPickingMediaWithInfo info: [UIImagePickerController.InfoKey: Any]
        ) {
            if let image = info[.originalImage] as? UIImage {
                onImage(image)
            }
        }

        func imagePickerControllerDidCancel(_ picker: UIImagePickerController) {
            picker.dismiss(animated: true)
        }
    }
}
