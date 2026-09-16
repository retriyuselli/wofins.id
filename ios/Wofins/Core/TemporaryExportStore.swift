import Foundation

enum TemporaryExportStore {
    private static let directoryName = "WofinsExports"
    private static let maximumAge: TimeInterval = 24 * 60 * 60

    static func persistDownloadedFile(at sourceURL: URL, fileName: String) throws -> URL {
        let directory = try exportDirectory()
        cleanup(in: directory)
        let safeName = URL(fileURLWithPath: fileName).lastPathComponent
        let destination = directory
            .appendingPathComponent(UUID().uuidString, isDirectory: true)
            .appendingPathComponent(safeName)
        try FileManager.default.createDirectory(
            at: destination.deletingLastPathComponent(),
            withIntermediateDirectories: true,
            attributes: [.protectionKey: FileProtectionType.complete]
        )
        try FileManager.default.moveItem(at: sourceURL, to: destination)
        try FileManager.default.setAttributes(
            [.protectionKey: FileProtectionType.complete],
            ofItemAtPath: destination.path
        )
        return destination
    }

    static func write(_ data: Data, fileName: String) throws -> URL {
        let directory = try exportDirectory()
        cleanup(in: directory)
        let safeName = URL(fileURLWithPath: fileName).lastPathComponent
        let destination = directory
            .appendingPathComponent(UUID().uuidString, isDirectory: true)
            .appendingPathComponent(safeName)
        try FileManager.default.createDirectory(
            at: destination.deletingLastPathComponent(),
            withIntermediateDirectories: true,
            attributes: [.protectionKey: FileProtectionType.complete]
        )
        try data.write(to: destination, options: [.atomic, .completeFileProtection])
        return destination
    }

    static func remove(_ url: URL?) {
        guard let url else { return }
        let directory = (try? exportDirectory())?.standardizedFileURL
        let candidate = url.standardizedFileURL
        guard let directory, candidate.path.hasPrefix(directory.path + "/") else { return }
        try? FileManager.default.removeItem(at: candidate.deletingLastPathComponent())
    }

    private static func exportDirectory() throws -> URL {
        let directory = FileManager.default.temporaryDirectory
            .appendingPathComponent(directoryName, isDirectory: true)
        try FileManager.default.createDirectory(
            at: directory,
            withIntermediateDirectories: true,
            attributes: [.protectionKey: FileProtectionType.complete]
        )
        return directory
    }

    private static func cleanup(in directory: URL, now: Date = Date()) {
        guard let entries = try? FileManager.default.contentsOfDirectory(
            at: directory,
            includingPropertiesForKeys: [.contentModificationDateKey],
            options: [.skipsHiddenFiles]
        ) else { return }
        for entry in entries {
            let date = try? entry.resourceValues(forKeys: [.contentModificationDateKey]).contentModificationDate
            if date == nil || now.timeIntervalSince(date!) > maximumAge {
                try? FileManager.default.removeItem(at: entry)
            }
        }
    }
}
