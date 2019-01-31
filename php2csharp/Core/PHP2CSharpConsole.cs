using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace PHP2CSharp.Core
{
    public class PHP2CSharpConsole
    {
        private PHP2CSharpConverter _php2csharp = new PHP2CSharpConverter();
        public string OriginDir { get; set; }
        public string DestinyDir { get; set; }

        private void executeDir(string dir) {
            string relativeDir = dir.Substring(OriginDir.Length);
            string destinyDir = DestinyDir;
            if (!string.IsNullOrEmpty(relativeDir)) {
                relativeDir = relativeDir.Substring(1);
                destinyDir = Path.Combine(DestinyDir, relativeDir);
            }
            if (!Directory.Exists(destinyDir)) {
                Directory.CreateDirectory(destinyDir);
            }
            Console.WriteLine(".\\" + relativeDir);
            string fullPathDir = Path.Combine(OriginDir, dir);
            foreach (var dirPath in Directory.GetDirectories(fullPathDir)) {
                executeDir(dirPath);
            }
            foreach (var file in Directory.GetFiles(fullPathDir)) {
                if (file.EndsWith(".php", true, null))
                {
                    string relativePath = file.Substring(OriginDir.Length + 1);
                    relativePath = relativePath.Substring(0, relativePath.Length - 3) + "cs";
                    string destinyPath = Path.Combine(DestinyDir, relativePath);

                    string origSource = File.ReadAllText(file);
                    var destinySource = _php2csharp.convert(origSource);
                    File.WriteAllText(destinyPath, destinySource);

                    Console.WriteLine(".\\" + relativePath);
                }
            }
        }

        public void execute()
        {
            if (!Directory.Exists(OriginDir))
            {
                throw new Exception(string.Format("Origin directory '{0}' not exists!", OriginDir));
            }

            if (!Directory.Exists(DestinyDir))
            {
                throw new Exception(string.Format("Destiny directory '{0}' not exists!", DestinyDir));
            }

            executeDir(OriginDir);

            /*
            string origFile = @"F:\Projetos\php2csharp\teste\AcaoInfo.php";
            string origSource = File.ReadAllText(origFile);
            var php2csharp = new PHP2CSharpConverter();
            var destinySource = php2csharp.convert(origSource);
            Console.Write(destinySource);
            */
        }
    }
}
